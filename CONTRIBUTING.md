# Contributing to Cadence

Thank you for your interest in contributing to Cadence! This guide will help you get started.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer 2.x
- Git

### Setup

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/cadence.git
   cd cadence
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

<br>

## Development Workflow

Look at existing issues to find something to work on, or open a new issue if you have an idea for a feature or bug fix.

### Running Tests

```bash
composer test
```

### Code Formatting

We use Laravel Pint for code formatting. Run before committing:

```bash
composer format
```

Check formatting without making changes:

```bash
composer format:check
```

### Project Structure

```
cadence/
├── bin/cadence              # CLI entry point
├── src/
│   ├── App/
│   │   ├── Ticker.php       # Main daemon loop
│   │   └── Logger.php       # Logging system
│   ├── Config/
│   │   ├── Config.php       # Configuration management
│   │   └── EnvLoader.php    # .env file loader
│   └── Console/
│       ├── Application.php  # CLI application
│       ├── ArgumentParser.php
│       └── CommandList.php
├── tests/                   # Test suite
```

<br>

## Making Changes

### Branch Naming

- `feature/your-feature-name` - New features
- `fix/issue-description` - Bug fixes
- `docs/what-you-updated` - Documentation updates

### Commit Messages

Write clear, concise commit messages, for example:

```
Add PID file support for external monitoring

- Write PID to file on daemon start
- Clean up PID file on graceful shutdown
- Add --pid-file CLI option
```

### Pull Request Process

1. Create a new branch from `main`
2. Make your changes
3. Run tests and formatting:
   ```bash
   composer format
   composer test
   ```
4. Push to your fork
5. Open a Pull Request with:
   - Clear description of changes and mention of related issues
   - Link to related issue (if any)
   - Screenshots/examples (if applicable)

<br>

## Code Guidelines

### Style

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Keep methods focused and small
- Add type hints for parameters and return types

### Testing

- Write tests for new features
- Update tests when modifying existing code
- Aim for meaningful test coverage, not just high percentages

### Documentation

- Update README.md for user-facing changes
- Add inline comments for complex logic

<br>

## Contribution for Documentation website

Documentation website's codebase is separated in `doc` branch. Check-out to the `doc` branch make changes in `docs/` directory and open a pull request for `doc` branch.

<br>

## Reporting Issues

### Bug Reports

Include:
- PHP version
- Cadence version
- Steps to reproduce
- Expected vs actual behavior
- Error messages/logs

### Feature Requests

Include:
- Use case description
- Proposed solution (if any)
- Alternatives considered

<br>

## Code of Conduct

- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Assume good intentions

## Questions?

- Open an issue for questions
- Check existing issues before creating new ones

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
