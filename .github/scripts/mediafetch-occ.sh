#!/usr/bin/env bash
set -euo pipefail

stage=$1
mode=${2:-check-tools}
mounts=(--volume "$stage:/stage")
if [[ "$mode" == sign ]]; then
  mounts+=(--volume "${3:?missing signing directory}:/keys:ro")
fi

docker run --rm --network none --user "$(id -u):$(id -g)" "${mounts[@]}" \
  --entrypoint bash nextcloud:32-apache -euc '
    work=$(mktemp -d)
    cp -R /usr/src/nextcloud/. "$work/"
    cd "$work"
    # Use Nextcloud defaults for this isolated SQLite installation.
    rm -f config/*.config.php
    php occ maintenance:install --database sqlite --data-dir "$work/data" --admin-user ci --admin-pass "$(openssl rand -hex 32)"
    php occ help integrity:check-app >/dev/null
    if [[ "$1" == sign ]]; then
      php occ integrity:sign-app --path=/stage/mediafetch --privateKey=/keys/mediafetch.key --certificate=/keys/mediafetch.crt
      test -s /stage/mediafetch/appinfo/signature.json
      php occ integrity:check-app --path=/stage/mediafetch mediafetch
    else
      php occ help integrity:sign-app >/dev/null
    fi
  ' bash "$mode"
