#!/bin/sh

php_bin=$(command -v php) || exit 127
hook_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd) || exit 1
occ="${hook_dir}/../../../occ"

exec "$php_bin" "$occ" aria2 complete "$1" "$2" "$3"
