#!/usr/bin/env bash
set -e

cd /code/

curl -L https://codeclimate.com/downloads/test-reporter/test-reporter-latest-linux-amd64 > ./cc-test-reporter
chmod +x ./cc-test-reporter

./cc-test-reporter before-build
./vendor/bin/phpunit --whitelist src --coverage-clover clover.xml
./cc-test-reporter after-build -t clover --debug
