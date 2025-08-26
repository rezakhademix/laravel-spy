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

echo "🏗️  Building Docker image..."
docker build -t laravel-spy-test .

echo "📦 Installing dependencies..."
docker run --rm -v "$(pwd)":/var/www laravel-spy-test composer install --no-scripts --prefer-dist

echo "🧪 Running PHPUnit tests..."
docker run --rm -v "$(pwd)":/var/www laravel-spy-test vendor/bin/phpunit

echo "✅ Tests completed!"
