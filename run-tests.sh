#!/bin/bash

# Laravel Spy Test Runner Script
# This script builds and runs tests in a Docker container

set -e

echo "🔍 Laravel Spy - Running Tests in Docker"
echo "========================================"

if ! docker info > /dev/null 2>&1; then
    echo "❌ Error: Docker is not running. Please start Docker and try again."
    exit 1
fi

echo "🏗  Building Docker image..."
docker build -t laravel-spy-test .

echo "📦 Installing dependencies..."
docker run --rm \
    -v "$(pwd)":/var/www \
    -w /var/www \
    -u $(id -u):$(id -g) \
    -e HOME=/tmp \
    laravel-spy-test bash -c "
        git config --global --add safe.directory /var/www 2>/dev/null || true && \
        mkdir -p /tmp/composer-cache && \
        export COMPOSER_CACHE_DIR=/tmp/composer-cache && \
        composer install --no-scripts --prefer-dist
    "

echo "🧪 Running PHPUnit tests..."
docker run --rm \
    -v "$(pwd)":/var/www \
    -w /var/www \
    -u $(id -u):$(id -g) \
    -e HOME=/tmp \
    laravel-spy-test bash -c "
        git config --global --add safe.directory /var/www 2>/dev/null || true && \
        vendor/bin/phpunit --display-deprecations
    "

echo "✅ Tests completed!"