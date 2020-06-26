#!/bin/bash
ulimit -n 300000
rm ./vendor -rf
composer update --no-dev
php ./vendor/bin/phing
composer update
