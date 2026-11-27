#!/usr/bin/env bash
# Chạy kiểm tra giống CI: code style + toàn bộ test.
set -euo pipefail
cd "$(dirname "$0")/../backend"
vendor/bin/pint --test
vendor/bin/pest "$@"
