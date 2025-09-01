# Contributing to Weather Forecaster Agent

Thank you for considering contributing to the Weather Forecaster Agent! This document outlines the process for contributing to this project.

## Code of Conduct

Please be respectful and considerate when interacting with other contributors. This project follows standard open source conduct guidelines.

## Getting Started

1. Fork the repository
2. Clone your fork to your local machine
3. Create a new branch for your feature or bug fix
4. Make your changes
5. Push your changes to your fork
6. Submit a pull request

## Development Environment Setup

1. Install PHP 8.2+ and Composer
2. Install Node.js and NPM
3. Clone the repository
4. Install dependencies
   ```bash
   composer install
   npm install
   ```
5. Set up your environment variables
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
6. Create the SQLite database
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```
7. Start the development server
   ```bash
   npm run dev
   php artisan serve
   ```

## Pull Request Process

1. Update the README.md or documentation with details of changes if appropriate
2. Run tests and ensure they pass
3. Update the CHANGELOG.md if appropriate
4. The PR will be merged once it has been reviewed and approved

## Coding Standards

This project follows Laravel's coding standards:

- PSR-2 coding standard
- PSR-4 autoloading standard
- Use Laravel's built-in validation
- Follow Laravel naming conventions for database tables and columns
- Use Laravel's Eloquent ORM for database interactions
- Write PHPUnit tests for new features

## Running Tests

```bash
php artisan test
```

## Feature Requests

Feature requests are welcome. Please provide as much detail and context as possible.

## Bug Reports

When filing a bug report, please include:

1. A clear description of the issue
2. Steps to reproduce the bug
3. Expected behavior
4. Actual behavior
5. Screenshots if applicable
6. Your environment details (OS, browser, PHP version, etc.)

## Commit Message Guidelines

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project.
