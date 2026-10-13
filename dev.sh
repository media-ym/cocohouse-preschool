#!/bin/bash
cd "$(dirname "$0")"
PHP="/tmp/php-coco/php"

if [[ ! -x "$PHP" ]]; then
  echo "PHP not found at $PHP"
  exit 1
fi

"$PHP" -d session.save_path=/tmp -S "[::1]:8765" router.php &
ipv6_pid=$!
cleanup() {
  kill "$ipv6_pid" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo "Cocohouse local: http://localhost:8765"
"$PHP" -d session.save_path=/tmp -S 127.0.0.1:8765 router.php
