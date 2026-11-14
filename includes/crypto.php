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
	return function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'sodium_crypto_secretbox_open' );
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

	foreach ( array( 'SECURE_AUTH_KEY', 'SECURE_AUTH_SALT' ) as $name ) {
		if ( ! defined( $name ) || '' === (string) constant( $name ) || 'put your unique phrase here' === constant( $name ) ) {
			return 'database';
		}
	}

	return 'config';
}

/**
 * The 32-byte encryption key.
 *
 * @return string Raw bytes.
 */
function if_reviews_crypto_key() {

	switch ( if_reviews_crypto_key_source() ) {
		case 'constant':
			$material = (string) IF_REVIEWS_ENCRYPTION_KEY;
			break;
		case 'config':
			$material = SECURE_AUTH_KEY . SECURE_AUTH_SALT;
			break;
		default:
			// WordPress keeps generated keys in the database; still better than nothing.
			$material = wp_salt( 'secure_auth' );
	}

	return hash_hmac( 'sha256', 'if-reviews-encryption-v1', $material, true );
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
 * Encrypt a string for storage. Empty strings stay empty.
 *
 * @param string $plain Value.
 * @return string Prefixed, base64-encoded nonce and ciphertext.
 */
function if_reviews_encrypt( $plain ) {

	$plain = (string) $plain;

	if ( '' === $plain || if_reviews_is_encrypted( $plain ) || ! if_reviews_crypto_available() ) {
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
 *                      decrypted (usually because the site's keys changed).
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

	$plain = sodium_crypto_secretbox_open(
		substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ),
		if_reviews_crypto_key()
	);

	return false === $plain ? false : $plain;
}
