#!/bin/bash
cd "$(dirname "$0")"
PHP="/tmp/php-coco/php"
PHP_URL="https://dl.static-php.dev/static-php-cli/common/php-8.3.27-cli-macos-x86_64.tar.gz"

if [[ ! -x "$PHP" ]]; then
  echo "PHP missing. Downloading it once..."
  mkdir -p /tmp/php-coco
  archive="$(mktemp)"
  if ! curl -fL --retry 3 --retry-delay 2 -A "Mozilla/5.0" "$PHP_URL" -o "$archive"; then
    echo "Could not download PHP. Check the internet connection and run npm run dev again."
    rm -f "$archive"
    exit 1
  fi
  tar -xzf "$archive" -C /tmp/php-coco
  chmod +x "$PHP"
  rm -f "$archive"
fi

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
