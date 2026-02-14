## IDE Setup

### VS Code Extensions

Install the following extensions:

- PHP Intelephense
- Laravel Extra Intellisense
- Laravel Blade Snippets
- Volar (Vue Language Features)
- TypeScript Vue Plugin (Volar)
- ESLint
- Prettier - Code formatter
- Tailwind CSS IntelliSense
- GitLens
- Docker

### VS Code Settings

```json
{
  "editor.formatOnSave": true,
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  },
  "php.suggest.basic": false,
  "intelephense.files.maxSize": 5000000,
  "typescript.tsdk": "frontend/node_modules/typescript/lib"
}
```

### PHPStorm Configuration

1. Enable Laravel plugin
2. Configure PHP interpreter (PHP 8.3+)
3. Configure Composer
4. Configure Node.js and npm
5. Enable Tailwind CSS support
6. Configure Vue.js support

---

## Troubleshooting

### Common Issues

#### 1. Composer Install Fails

```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Try install again
composer install --ignore-platform-reqs
```

#### 2. Migration Errors

```bash
# Reset database
php artisan migrate:fresh

# Rollback and re-migrate
php artisan migrate:rollback
php artisan migrate
```

#### 3. Permission Errors

```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 4. Frontend Build Errors

```bash
# Clear node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Clear Vite cache
rm -rf frontend/.vite
```

#### 5. Docker Issues

```bash
# Rebuild containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# View logs
docker-compose logs -f app

# Execute command in container
docker-compose exec app bash
```

### Database Connection Issues

1. Verify PostgreSQL is running
2. Check database credentials in .env
3. Ensure database exists
4. Check firewall rules
5. Verify pg_hba.conf settings

### Redis Connection Issues

1. Verify Redis is running: `redis-cli ping`
2. Check Redis credentials in .env
3. Verify Redis port is not blocked
4. Clear Redis cache: `redis-cli FLUSHALL`

---

## Development Workflow

### 1. Create Feature Branch

```bash
git checkout -b feature/my-feature
```

### 2. Make Changes

- Write code following coding standards
- Write tests for new features
- Update documentation

### 3. Run Tests and Checks

```bash
# Backend
php artisan test
./vendor/bin/phpstan analyse
./vendor/bin/pint

# Frontend
npm run test
npm run lint
npm run type-check
```

### 4. Commit Changes

```bash
git add .
git commit -m "feat: add new feature"
```

Follow [Conventional Commits](https://www.conventionalcommits.org/):
- `feat:` for new features
- `fix:` for bug fixes
- `docs:` for documentation
- `refactor:` for code refactoring
- `test:` for tests
- `chore:` for maintenance

### 5. Push and Create Pull Request

```bash
git push origin feature/my-feature
```
