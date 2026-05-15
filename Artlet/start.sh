#!/bin/bash

echo "Starting server..."

composer install

php -S 0.0.0.0:$PORT -t .