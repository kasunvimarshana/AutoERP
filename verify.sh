#!/bin/bash

# AutoERP - Installation Verification Script
# This script verifies the installation and setup of the platform

echo "=========================================="
echo "AutoERP"
echo "Installation Verification"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0

# Helper functions
check_pass() {
    echo -e "${GREEN}✓ $1${NC}"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}✗ $1${NC}"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Check backend structure
echo "Checking Backend Structure..."
echo "=========================================="

if [ -d "backend/app/Base" ]; then
    check_pass "Base classes directory exists"
else
    check_fail "Base classes directory missing"
fi

if [ -d "backend/app/Modules" ]; then
    check_pass "Modules directory exists"
    
    # Check each module
    MODULES=("Auth" "Tenant" "User" "Role" "Permission" "Customer" "Vehicle" "Inventory" "Product" "Order" "Invoice" "Payment")
    for module in "${MODULES[@]}"; do
        if [ -d "backend/app/Modules/$module" ]; then
            check_pass "Module $module exists"
        else
            check_fail "Module $module missing"
        fi
    done
else
    check_fail "Modules directory missing"
fi

if [ -f "backend/composer.json" ]; then
    check_pass "Composer configuration exists"
else
    check_fail "Composer configuration missing"
fi

if [ -d "backend/vendor" ]; then
    check_pass "Backend dependencies installed"
else
    check_warn "Backend dependencies not installed (run: cd backend && composer install)"
fi

if [ -f "backend/.env" ]; then
    check_pass "Backend environment file exists"
else
    check_warn "Backend environment file missing (copy from .env.example)"
fi

echo ""
echo "Checking Frontend Structure..."
echo "=========================================="

if [ -d "frontend/src/modules" ]; then
    check_pass "Frontend modules directory exists"
else
    check_fail "Frontend modules directory missing"
fi

if [ -d "frontend/src/components" ]; then
    check_pass "Frontend components directory exists"
else
    check_fail "Frontend components directory missing"
fi

if [ -f "frontend/package.json" ]; then
    check_pass "Frontend package configuration exists"
else
    check_fail "Frontend package configuration missing"
fi

if [ -d "frontend/node_modules" ]; then
    check_pass "Frontend dependencies installed"
else
    check_warn "Frontend dependencies not installed (run: cd frontend && npm install)"
fi

if [ -f "frontend/.env" ]; then
    check_pass "Frontend environment file exists"
else
    check_warn "Frontend environment file missing (copy from .env.example)"
fi

echo ""
echo "Checking Infrastructure..."
echo "=========================================="

if [ -f "docker-compose.yml" ]; then
    check_pass "Docker Compose configuration exists"
else
    check_fail "Docker Compose configuration missing"
fi

if [ -f "setup.sh" ]; then
    check_pass "Setup script exists"
else
    check_fail "Setup script missing"
fi

if [ -f ".gitignore" ]; then
    check_pass "Git ignore file exists"
else
    check_warn "Git ignore file missing"
fi

echo ""
echo "Checking Documentation..."
echo "=========================================="

DOCS=("PLATFORM_OVERVIEW.md" "IMPLEMENTATION_GUIDE.md" "PROJECT_SUMMARY.md")
for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        check_pass "Documentation: $doc"
    else
        check_warn "Documentation missing: $doc"
    fi
done

echo ""
echo "=========================================="
echo "Verification Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All critical checks passed!${NC}"
    echo ""
    echo "Next Steps:"
    echo "1. Configure database in backend/.env"
    echo "2. Run: cd backend && php artisan migrate"
    echo "3. Start backend: cd backend && php artisan serve"
    echo "4. Start frontend: cd frontend && npm run dev"
    echo ""
    echo "Access Points:"
    echo "- Frontend: http://localhost:5173"
    echo "- Backend API: http://localhost:8000/api"
    echo "- API Docs: http://localhost:8000/api/documentation"
else
    echo -e "${RED}✗ Some checks failed. Please review the output above.${NC}"
    exit 1
fi

echo "=========================================="
