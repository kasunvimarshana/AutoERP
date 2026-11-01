# AutoERP Complete Local Verification Suite
$ErrorActionPreference = "Stop"

Write-Host "==> 1. Validating Composer..." -ForegroundColor Cyan
composer validate --strict
composer check-platform-reqs
composer security:audit

Write-Host "==> 2. Auditing NPM dependencies..." -ForegroundColor Cyan
npm audit

Write-Host "==> 3. Running TypeScript Typecheck..." -ForegroundColor Cyan
npm run typecheck

Write-Host "==> 4. Running ESLint..." -ForegroundColor Cyan
npm run lint

Write-Host "==> 5. Running Frontend Vitest..." -ForegroundColor Cyan
npm test -- --run

Write-Host "==> 6. Running Vite Production Build..." -ForegroundColor Cyan
npm run build

Write-Host "==> 7. Running Python Financial Integrity Audits..." -ForegroundColor Cyan
python -B -m unittest discover -s docs/audits -p 'test_*.py' -v

Write-Host "==> 8. Running MySQL Bootstrap Safety Checks..." -ForegroundColor Cyan
python -B -m unittest discover -s tests/bootstrap -p 'test_*.py' -v

Write-Host "==> 9. Running Full Backend Test Suite (SQLite)..." -ForegroundColor Cyan
php vendor/bin/phpunit

Write-Host "==> All AutoERP Verification Checks Passed Successfully!" -ForegroundColor Green
