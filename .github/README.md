# CI/CD Pipeline Documentation

This directory contains the GitHub Actions workflows that implement the CI/CD pipeline for the Guestbook application.

## Workflows

### 1. CI Pipeline (`.github/workflows/ci.yml`)

The main CI pipeline that runs on every push and pull request to the main/master branches.

#### Jobs:

- **Code Quality Checks**: 
  - PHP syntax validation
  - PHPStan static analysis
  - PHP-CS-Fixer code style checking
  - Composer validation

- **Security Scanning**:
  - Composer audit for vulnerabilities
  - Secret scanning with TruffleHog
  - Dependency review

- **Test Suite**:
  - Unit and integration tests with PHPUnit
  - Matrix testing across PHP 8.1, 8.2, 8.3
  - MySQL database testing
  - Test coverage reporting

- **Docker Build & Test**:
  - Docker image building
  - Container security scanning with Trivy
  - Database connectivity testing

- **Build Verification**:
  - Production build validation
  - Artifact creation and storage

#### Triggers:
- Push to `main` or `master` branches
- Pull requests to `main` or `master` branches

### 2. Security Scanning (`.github/workflows/security.yml`)

Comprehensive security scanning workflow that runs independently.

#### Jobs:

- **CodeQL Analysis**: Static application security testing (SAST)
- **Dependency Review**: Checks for vulnerable dependencies in PRs
- **Secret Scanning**: Detects secrets and credentials in code
- **Snyk Security Scan**: Additional vulnerability scanning
- **License Compliance**: Checks license compatibility

#### Triggers:
- Push to `main` or `master` branches
- Pull requests to `main` or `master` branches
- Daily schedule at 2 AM UTC

## Configuration Files

### PHPStan Configuration (`phpstan.neon`)
- Level 8 static analysis
- Excludes test files
- Custom error ignore patterns
- PHPUnit integration

### PHP-CS-Fixer Configuration (`.php-cs-fixer.dist.php`)
- PSR-12 coding standards
- PHP 8.0+ migration rules
- Ordered imports
- Header comment enforcement

## Environment Variables

The pipeline uses the following environment variables:

### Test Environment:
- `TEST_DB_HOST`: MySQL host for testing
- `TEST_DB_USER`: MySQL user for testing
- `TEST_DB_PASSWORD`: MySQL password for testing
- `TEST_DB_NAME`: MySQL database name for testing
- `TEST_MODE`: Test mode flag

### Security Scanning:
- `SNYK_TOKEN`: Snyk API token (stored as GitHub secret)

## Artifacts

The pipeline generates the following artifacts:

- **Test Coverage Reports**: HTML coverage reports
- **Build Artifacts**: Production-ready application files
- **Security Scan Results**: SARIF files for security findings
- **Test Reports**: PHPUnit XML reports

## Notifications

The pipeline provides notifications through:

- **GitHub Checks**: Status checks on commits and PRs
- **Build Status**: Success/failure indicators
- **Security Alerts**: Automatic security vulnerability reporting

## Usage

### Running Locally

You can run the same checks locally:

```bash
# Code quality checks
composer validate
find src tests -name "*.php" -exec php -l {} \;
vendor/bin/phpstan analyse src
vendor/bin/php-cs-fixer fix --dry-run --diff

# Security checks
composer audit
trufflehog git file://. --debug --only-verified

# Tests
vendor/bin/phpunit
```

### Customizing the Pipeline

To modify the pipeline:

1. **Add new jobs**: Edit the workflow files in `.github/workflows/`
2. **Change PHP versions**: Update the matrix in the test job
3. **Add new checks**: Create new jobs or steps
4. **Modify triggers**: Update the `on:` section

### Adding New Dependencies

When adding new dependencies:

1. Update `composer.json`
2. Run `composer update`
3. Check license compatibility
4. Verify no security vulnerabilities
5. Update the CI pipeline if needed

## Best Practices

### Commit Messages
- Use descriptive commit messages
- Reference related issues or PRs
- Follow conventional commit format when possible

### Pull Requests
- Ensure all CI checks pass before merging
- Address any security vulnerabilities
- Maintain test coverage
- Follow code style guidelines

### Security
- Never commit secrets or credentials
- Use GitHub secrets for sensitive information
- Regularly update dependencies
- Monitor security alerts

## Troubleshooting

### Common Issues

1. **Test Failures**: Check test database setup and environment variables
2. **Code Style Errors**: Run PHP-CS-Fixer locally to fix formatting
3. **Security Scans**: Review and address any vulnerabilities found
4. **Build Failures**: Check PHP version compatibility and dependencies

### Getting Help

- Check GitHub Actions logs for detailed error messages
- Review workflow files for configuration issues
- Consult the [GitHub Actions documentation](https://docs.github.com/en/actions)
- Check the [PHPStan documentation](https://phpstan.org/user-guide/getting-started)
- Review the [PHP-CS-Fixer documentation](https://cs.symfony.com/doc/index.html)