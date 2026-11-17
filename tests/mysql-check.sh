#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
fixture="editora-dbinterface-test-$(id -u)-$$"
image="${IMAGE:-upgrades-package-tests:8.5.12}"
cleanup() { docker rm -f "$fixture" >/dev/null 2>&1 || true; docker network rm "$fixture" >/dev/null 2>&1 || true; }
trap cleanup EXIT
docker network create --internal "$fixture" >/dev/null
docker run -d --name "$fixture" --network "$fixture" --tmpfs /var/lib/mysql \
  -e MYSQL_ROOT_PASSWORD=fixture-only -e MYSQL_DATABASE=editora_testing \
  --health-cmd='MYSQL_PWD=fixture-only mysql -h127.0.0.1 -uroot -e "SELECT 1" editora_testing' \
  --health-interval=2s --health-timeout=3s --health-retries=60 \
  mysql:8.4@sha256:76f0358884cb92d9a086d24e5671c38048d3fef40f2588703d73eb55392d6b69 >/dev/null
ready=false
for attempt in $(seq 1 90); do
  if [ "$(docker inspect --format '{{.State.Health.Status}}' "$fixture")" = healthy ]; then ready=true; break; fi
  sleep 1
done
[ "$ready" = true ] || { echo 'Fixture MySQL did not become healthy'; exit 1; }
docker run --rm --network "$fixture" --user "$(id -u):$(id -g)" -e TEST_DB_HOST="$fixture" \
  -v "$PWD:/app:ro" -w /app "$image" php vendor/bin/phpunit --testsuite MySQL --cache-directory=/tmp/dbinterface-phpunit
