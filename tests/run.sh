#!/usr/bin/env bash
# Runs the regression tests in a throwaway WordPress (Docker).
#
#   bash tests/run.sh
#
# Needs Docker. Starts MariaDB and WordPress on a private network, mounts
# this plugin plus the test mocks, runs tests/cases.php phase by phase with
# WP-CLI, and removes everything afterwards. Google is never contacted:
# tests/mu/google-mock.php answers every Google request.
set -euo pipefail

export MSYS_NO_PATHCONV=1 # Git Bash on Windows: leave container paths alone.

here() { (cd "$1" && (pwd -W 2>/dev/null || pwd)); }
PLUGIN="$(here "$(dirname "$0")/..")"
TESTS="$(here "$(dirname "$0")")"
NAME="ifr-test-$$"
WP_IMAGE="${WP_IMAGE:-wordpress:php8.3-apache}"
CLI_IMAGE="${CLI_IMAGE:-wordpress:cli-php8.3}"

cleanup() {
	docker rm -f "$NAME-wp" "$NAME-db" >/dev/null 2>&1 || true
	docker volume rm "$NAME-html" >/dev/null 2>&1 || true
	docker network rm "$NAME" >/dev/null 2>&1 || true
}
trap cleanup EXIT

DB_ENV=(-e WORDPRESS_DB_HOST="$NAME-db" -e WORDPRESS_DB_USER=wp -e WORDPRESS_DB_PASSWORD=wp -e WORDPRESS_DB_NAME=wp)

docker network create "$NAME" >/dev/null
docker run -d --name "$NAME-db" --network "$NAME" \
	-e MARIADB_ROOT_PASSWORD=root -e MARIADB_DATABASE=wp -e MARIADB_USER=wp -e MARIADB_PASSWORD=wp \
	mariadb:11 >/dev/null
docker run -d --name "$NAME-wp" --network "$NAME" "${DB_ENV[@]}" -v "$NAME-html":/var/www/html "$WP_IMAGE" >/dev/null

for _ in $(seq 1 60); do docker exec "$NAME-wp" test -f /var/www/html/wp-config.php 2>/dev/null && break; sleep 2; done

# wp <env...> -- <wp-cli args...>
wp() {
	local envs=()
	while [ "$1" != "--" ]; do envs+=(-e "$1"); shift; done
	shift
	docker run --rm --network "$NAME" "${DB_ENV[@]}" "${envs[@]+"${envs[@]}"}" \
		-v "$NAME-html":/var/www/html \
		-v "$PLUGIN":/var/www/html/wp-content/plugins/if-reviews \
		-v "$TESTS/mu":/var/www/html/wp-content/mu-plugins \
		--user 33 "$CLI_IMAGE" wp "$@"
}

for _ in $(seq 1 30); do wp -- db check >/dev/null 2>&1 && break; sleep 3; done
wp -- core install --url=http://localhost --title=Test --admin_user=admin --admin_password=admin \
	--admin_email=admin@example.test --skip-email >/dev/null
wp -- plugin activate if-reviews >/dev/null

phase() { wp IFR_PHASE="$1" "${@:2}" -- eval-file wp-content/plugins/if-reviews/tests/cases.php --user=admin; }

phase main
phase dedicated-key IFR_ENCRYPTION_KEY=first-dedicated-key
phase wrong-key IFR_ENCRYPTION_KEY=a-different-key
phase constant-credentials IFR_CONSTANT_CREDENTIALS=1

# A site whose wp-config.php still has the sample placeholder keys.
docker exec "$NAME-wp" sh -c "cp wp-config.php wp-config.bak && sed -i -E \"s/^define\\( *'(SECURE_AUTH_KEY|SECURE_AUTH_SALT)'.*/define( '\\1', 'put your unique phrase here' );/\" wp-config.php"
phase database-key
docker exec "$NAME-wp" sh -c "mv wp-config.bak wp-config.php"
phase keys-restored

phase before-uninstall
wp -- plugin uninstall if-reviews --deactivate --skip-delete >/dev/null
phase after-uninstall

echo "All tests passed."
