#!/bin/bash
# Setup script for Cake Shop Training Lab

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=========================================="
echo "Cake Shop OWASP Training Lab - Setup"
echo "=========================================="
echo ""

# Check PHP
echo "✓ Checking PHP..."
if ! command -v php &> /dev/null; then
    echo "✗ PHP not found. Please install PHP 7.4+"
    exit 1
fi
PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9.]+')
echo "  PHP version: $PHP_VERSION"
echo ""

# Check MySQL
echo "✓ Checking MySQL..."
if ! command -v mysql &> /dev/null; then
    echo "✗ MySQL not found. Please install MySQL 5.7+"
    exit 1
fi
MYSQL_VERSION=$(mysql --version | grep -oP '\d+\.\d+\.\d+')
echo "  MySQL version: $MYSQL_VERSION"
echo ""

# Create .env if not exists
echo "✓ Checking environment..."
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    cp "$PROJECT_ROOT/.env.example" "$PROJECT_ROOT/.env"
    echo "  Created .env from template"
    echo "  ⚠ Update .env with your database credentials"
else
    echo "  .env already exists"
fi
echo ""

# Create uploads directory
echo "✓ Setting up directories..."
mkdir -p "$PROJECT_ROOT/public/uploads"
mkdir -p "$PROJECT_ROOT/logs"
chmod 755 "$PROJECT_ROOT/public/uploads"
echo "  Directories ready"
echo ""

echo "=========================================="
echo "✅ Setup complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Update .env with your database credentials"
echo "2. Create MySQL database: mysql -u root -p < database/schema.sql"
echo "3. Start server: php -S localhost:8000 -t public/"
echo "4. Visit http://localhost:8000"
echo ""
