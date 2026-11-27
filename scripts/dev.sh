#!/usr/bin/env bash
# Chạy web (8000), queue worker, scheduler và Vite cùng lúc. Ctrl+C để dừng.
set -euo pipefail
cd "$(dirname "$0")/../backend"
npx concurrently -k -n web,queue,schedule,vite -c "#7A4A2B,#3B82F6,#10B981,#F59E0B" \
  "php artisan serve --port=8000" \
  "php artisan queue:listen --tries=3" \
  "php artisan schedule:work" \
  "npm run dev"
