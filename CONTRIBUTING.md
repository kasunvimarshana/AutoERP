# Contributing to Modular SaaS Vehicle Service

Thank you for your interest in contributing! This project follows strict architectural patterns and coding standards.

## Getting Started

1. Fork the repository
2. Clone your fork
3. Create a feature branch
4. Make your changes
5. Run tests and quality checks
6. Submit a pull request

## Architectural Guidelines

This project strictly follows:
- **Clean Architecture**
- **Controller → Service → Repository** pattern
- **SOLID** principles
- **DRY** and **KISS** principles

### Module Structure

All modules must follow this structure:
```
modules/{ModuleName}/
├── Controllers/
├── Services/
├── Repositories/
├── Models/
├── Migrations/
├── Requests/
├── Policies/
├── Events/
├── Listeners/
├── Resources/
└── Tests/
```

## Coding Standards

### PHP

- Follow PSR-12 coding standard
- Use type hints for all parameters and return types
- Document all public methods with PHPDoc
- Keep methods focused and single-purpose
- Maximum method length: 50 lines

### Testing

- Write tests for all new features
- Maintain minimum 80% code coverage
- Unit tests for services and repositories
- Integration tests for controllers
- Test names must be descriptive: `test_method_name_scenario_expected_result`

### Commits

- Use conventional commit messages:
  - `feat:` New feature
  - `fix:` Bug fix
  - `docs:` Documentation
  - `test:` Tests
  - `refactor:` Code refactoring
  - `style:` Formatting
  - `chore:` Maintenance

Example: `feat: add vehicle ownership transfer functionality`

## Pull Request Process

1. **Update documentation** if you change functionality
2. **Add tests** for new features
3. **Run quality checks**:
   ```bash
   php artisan test
   ./vendor/bin/phpstan analyse
   ./vendor/bin/pint --test
   ```
4. **Update CHANGELOG.md** with your changes
5. **Request review** from maintainers

## Module Development

When creating a new module:

1. Follow the structure in existing modules (Customer, Vehicle)
2. Implement all layers: Model → Repository → Service → Controller
3. Use transactions for all data modifications
4. Implement proper error handling
5. Log important activities
6. Fire events for asynchronous workflows
7. Write comprehensive tests

See [MODULE_DEVELOPMENT.md](docs/MODULE_DEVELOPMENT.md) for details.

## Cross-Module Interactions

- Never call repositories from other modules directly
- Always inject and use services for cross-module operations
- Use transactions to ensure atomicity
- Fire events for decoupled communication

## Code Review Checklist

Before submitting, ensure:
- [ ] Code follows architectural patterns
- [ ] All tests pass
- [ ] PHPStan analysis passes
- [ ] Code style is correct (Pint)
- [ ] Documentation is updated
- [ ] No security vulnerabilities
- [ ] Performance is acceptable
- [ ] Error handling is proper
- [ ] Logging is appropriate
- [ ] Multi-tenancy is enforced

## Questions?

- Review [ARCHITECTURE.md](ARCHITECTURE.md)
- Check [MODULE_DEVELOPMENT.md](docs/MODULE_DEVELOPMENT.md)
- Look at existing modules for examples
- Open an issue for clarification

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
