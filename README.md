# Guestbook Application

A modern PHP guestbook application with comprehensive testing, Docker containerization, and security best practices.

## Features

- User registration and authentication
- Guestbook message posting and management
- Admin panel for message approval/moderation
- Pagination for message display
- Comprehensive test suite with PHPUnit
- Docker containerization for easy deployment

## Installation

### Prerequisites

- Docker and Docker Compose
- Git

### Quick Start

1. Clone the repository:
```bash
git clone https://github.com/yourusername/guestbook.git
cd guestbook
```

2. Start the application:
```bash
docker-compose up -d
```

3. Install dependencies:
```bash
docker-compose exec app composer install
```

4. Access the application:
- Main application: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## Running Tests

### Run all tests:
```bash
docker-compose exec app composer test
```

### Run specific test suite:
```bash
docker-compose exec app vendor/bin/phpunit tests/Unit/
docker-compose exec app vendor/bin/phpunit tests/Integration/
```

### Generate code coverage:
```bash
docker-compose exec app vendor/bin/phpunit --coverage-html build/coverage
```

Coverage report will be available at: http://localhost:8080/build/coverage/

### Test Output Explanation:
- ✅ **Tests passed**: All tests are working correctly
- ⚠️ **PHPUnit Warnings**: Non-critical warnings (like missing Xdebug for coverage)
- ⚠️ **PHPUnit Deprecations**: Minor internal deprecation warnings in PHPUnit
- **Exit code 1**: Normal when warnings/deprecations are present, doesn't indicate test failures

## Project Structure

```
├── incs/                    # Core application logic
│   ├── db.php              # Database connection
│   ├── functions.php       # Application functions
│   └── Pagination.php      # Pagination class
├── tests/                   # Test suite
│   ├── BaseTestCase.php    # Base test class with database setup
│   ├── Unit/               # Unit tests
│   │   └── UserTest.php    # User registration tests
│   └── Integration/        # Integration tests
│       └── DatabaseTest.php # Database structure tests
├── views/                   # Template files
│   ├── index.tpl.php       # Main page template
│   ├── login.tpl.php       # Login form template
│   ├── register.tpl.php    # Registration form template
│   └── incs/               # Include templates
├── docker-compose.yml      # Docker configuration
├── Dockerfile              # PHP application container
├── phpunit.xml             # PHPUnit configuration
└── composer.json           # PHP dependencies
```

## Security Features

- Password hashing with `password_hash()`
- CSRF token protection
- Input validation and sanitization
- SQL injection prevention with prepared statements
- Security headers implementation
- Rate limiting for login/registration attempts

## Development

### Adding New Tests

1. Create test files in `tests/Unit/` or `tests/Integration/`
2. Extend `App\Tests\BaseTestCase` for database tests
3. Use the provided helper methods for test data creation

### Code Standards

- Follow PSR-4 autoloading standards
- Use type hints where possible
- Write comprehensive tests for new features
- Maintain security best practices

## Technologies Used

- PHP 8.2
- MySQL 8.0
- Valitron (validation library)
- PHPUnit (testing framework)
- Docker & Docker Compose

## License

This project is open source and available under the [MIT License](LICENSE).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for your changes
5. Submit a pull request