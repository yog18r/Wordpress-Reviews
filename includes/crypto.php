<?php
/**
 * Encryption for private data the plugin stores.
 *
 * Rule: anything the plugin keeps that is not shown publicly (API
 * credentials, OAuth tokens, account identifiers) is encrypted before it
 * is written to the database. Reviews themselves are public and are not.
 *
 * Values are sealed with libsodium's secretbox (XSalsa20-Poly1305, built
 * into PHP 7.2+ and polyfilled by WordPress). The key is derived from the
 * site's security keys in wp-config.php, so a copy of the database on its
 * own, such as a leaked backup or a staging clone, cannot be decrypted.
 * Define IF_REVIEWS_ENCRYPTION_KEY in wp-config.php to use a dedicated key.
 *
 * Encryption fails closed: if it is unavailable, private values are not
 * stored at all rather than stored in plain text.
 *
 * When the key source changes (security keys added to wp-config.php, or a
 * dedicated key defined), values sealed with the previous key are still
 * readable once and are then re-encrypted with the current key.
 *
 * This file is also loaded by uninstall.php, so it must not depend on the
 * rest of the plugin.
 *
 * @package IF_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'IF_REVIEWS_CRYPTO_PREFIX' ) ) {
	define( 'IF_REVIEWS_CRYPTO_PREFIX', 'ifr1:' );
}

/**
 * Whether encryption is available on this server.
 *
 * @return bool
 */
function if_reviews_crypto_available() {
	$available = function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'sodium_crypto_secretbox_open' );

	/**
	 * Filter whether encryption is available. Returning false stops the
	 * plugin storing private values at all; it never makes it store them
	 * unencrypted.
	 *
	 * @param bool $available
	 */
	return (bool) apply_filters( 'if_reviews_crypto_available', $available );
}

/**
 * Whether the security keys in wp-config.php are real (not missing or the
 * sample placeholder).
 *
 * @return bool
 */
function if_reviews_crypto_config_keys_set() {
	foreach ( array( 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT' ) as $name ) {
		if ( ! defined( $name ) || '' === (string) constant( $name ) || 'put your unique phrase here' === constant( $name ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Where the encryption key comes from.
 *
 * @return string 'constant' (IF_REVIEWS_ENCRYPTION_KEY), 'config' (security
 *                keys in wp-config.php) or 'database' (WordPress generated
 *                the keys and stores them in the database).
 */
function if_reviews_crypto_key_source() {

	if ( defined( 'IF_REVIEWS_ENCRYPTION_KEY' ) && '' !== (string) IF_REVIEWS_ENCRYPTION_KEY ) {
		return 'constant';
	}

	return if_reviews_crypto_config_keys_set() ? 'config' : 'database';
}

/**
 * Whether private values are protected from a database-only leak: encryption
 * works and the key is not kept in the database.
 *
 * @return bool
 */
function if_reviews_crypto_secure() {
	return if_reviews_crypto_available() && 'database' !== if_reviews_crypto_key_source();
}

/**
 * Turn key material into a 32-byte key.
 *
 * @param string $material Secret material.
 * @return string Raw bytes.
 */
function if_reviews_crypto_derive( $material ) {
	return hash_hmac( 'sha256', 'if-reviews-encryption-v1', (string) $material, true );
}

/**
 * The current 32-byte encryption key.
 *
 * @return string Raw bytes.
 */
function if_reviews_crypto_key() {
	$keys = if_reviews_crypto_keys();
	return $keys[0];
}

/**
 * Keys to try when decrypting: the current key first, then keys an
 * earlier configuration of this site would have used.
 *
 * @return string[] Raw 32-byte keys.
 */
function if_reviews_crypto_keys() {

	$source    = if_reviews_crypto_key_source();
	$materials = array();

	if ( 'constant' === $source ) {
		$materials[] = (string) IF_REVIEWS_ENCRYPTION_KEY;
	}

	if ( if_reviews_crypto_config_keys_set() ) {
		$materials[] = SECURE_AUTH_KEY . SECURE_AUTH_SALT;
	}

	// The keys WordPress generates and keeps in the database when
	// wp-config.php has none. Current key only when nothing better exists;
	// otherwise kept for reading values saved before keys were added.
	$db_key  = get_site_option( 'secure_auth_key' );
	$db_salt = get_site_option( 'secure_auth_salt' );

	if ( 'database' === $source ) {
		$materials[] = wp_salt( 'secure_auth' );
	} elseif ( $db_key && $db_salt ) {
		$materials[] = $db_key . $db_salt;
	}

	return array_values( array_unique( array_map( 'if_reviews_crypto_derive', $materials ) ) );
}

/**
 * Note, or ask, whether a value was just read with an older key and should
 * be saved again.
 *
 * @param bool|null $set True to record it; null to read.
 * @return bool
 */
function if_reviews_crypto_used_old_key( $set = null ) {
	static $used = false;
	if ( null !== $set ) {
		$used = (bool) $set;
	}
	return $used;
}

/**
 * Whether a stored value is in the encrypted format.
 *
 * @param mixed $value Stored value.
 * @return bool
 */
function if_reviews_is_encrypted( $value ) {
	return is_string( $value ) && 0 === strpos( $value, IF_REVIEWS_CRYPTO_PREFIX );
}

/**
 * Encrypt a string for storage.
 *
 * Empty strings stay empty. If encryption is unavailable an empty string is
 * returned, so the value is never stored in plain text.
 *
 * @param string $plain Value.
 * @return string Prefixed, base64-encoded nonce and ciphertext, or ''.
 */
function if_reviews_encrypt( $plain ) {

	$plain = (string) $plain;

	if ( '' === $plain ) {
		return '';
	}

	if ( ! if_reviews_crypto_available() ) {
		return '';
	}

	if ( if_reviews_is_encrypted( $plain ) ) {
		return $plain;
	}

	$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
	$sealed = sodium_crypto_secretbox( $plain, $nonce, if_reviews_crypto_key() );

	return IF_REVIEWS_CRYPTO_PREFIX . base64_encode( $nonce . $sealed ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- binary-safe storage, not obfuscation.
}

/**
 * Decrypt a stored value.
 *
 * Values saved before encryption was added are returned as they are.
 *
 * @param string $stored Stored value.
 * @return string|false The plain value, or false when it cannot be
 *                      decrypted with the current or an earlier key.
 */
function if_reviews_decrypt( $stored ) {

	$stored = (string) $stored;

	if ( ! if_reviews_is_encrypted( $stored ) ) {
		return $stored;
	}

	if ( ! if_reviews_crypto_available() ) {
		return false;
	}

	$raw = base64_decode( substr( $stored, strlen( IF_REVIEWS_CRYPTO_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- see if_reviews_encrypt().

	if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES ) {
		return false;
	}

	$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
	$sealed = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

	foreach ( if_reviews_crypto_keys() as $index => $key ) {
		$plain = sodium_crypto_secretbox_open( $sealed, $nonce, $key );
		if ( false !== $plain ) {
			if ( $index > 0 ) {
				if_reviews_crypto_used_old_key( true );
			}
			return $plain;
		}
	}

	return false;
}
