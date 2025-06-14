# Contributing to ChronoForge

Thank you for your interest in contributing to ChronoForge! We welcome contributions from the community and are pleased to have you join us.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Submitting Changes](#submitting-changes)
- [Reporting Issues](#reporting-issues)

## 🤝 Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

### Our Standards

- **Be respectful** - Treat everyone with respect and kindness
- **Be inclusive** - Welcome newcomers and help them learn
- **Be collaborative** - Work together towards common goals
- **Be constructive** - Provide helpful feedback and suggestions

## 🚀 Getting Started

### Prerequisites

Before contributing, ensure you have:

- **WordPress Development Environment** - Local WordPress installation
- **PHP 7.4+** - Required for development
- **Composer** - For dependency management
- **Git** - For version control
- **Code Editor** - VS Code, PhpStorm, or similar

### Development Tools

Recommended tools for development:

- **WordPress CLI** - For WordPress management
- **PHPUnit** - For unit testing
- **PHP_CodeSniffer** - For code standards checking
- **Xdebug** - For debugging

## 🔧 Development Setup

### 1. Fork and Clone

```bash
# Fork the repository on GitHub
# Clone your fork
git clone https://github.com/your-username/chrono-forge.git
cd chrono-forge
```

### 2. Install Dependencies

```bash
# Install Composer dependencies
cd chrono-forge/
composer install
```

### 3. WordPress Setup

```bash
# Copy plugin to WordPress plugins directory
cp -r chrono-forge/ /path/to/wordpress/wp-content/plugins/

# Activate plugin in WordPress admin
# Or use WP-CLI:
wp plugin activate chrono-forge
```

### 4. Development Environment

```bash
# Enable WordPress debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

## 🛠️ How to Contribute

### Types of Contributions

We welcome several types of contributions:

1. **Bug Reports** - Help us identify and fix issues
2. **Feature Requests** - Suggest new functionality
3. **Code Contributions** - Submit bug fixes or new features
4. **Documentation** - Improve or add documentation
5. **Translations** - Help translate the plugin
6. **Testing** - Test new features and report issues

### Contribution Workflow

1. **Check existing issues** - Look for existing reports or requests
2. **Create an issue** - Discuss your idea before coding
3. **Fork the repository** - Create your own copy
4. **Create a branch** - Use descriptive branch names
5. **Make changes** - Follow coding standards
6. **Test thoroughly** - Ensure your changes work
7. **Submit a pull request** - Describe your changes clearly

## 📝 Coding Standards

### PHP Standards

Follow WordPress coding standards and PSR-12:

```php
<?php
/**
 * Class description
 *
 * @package ChronoForge
 * @since 1.0.0
 */

namespace ChronoForge\Core;

class ExampleClass {
    
    /**
     * Method description
     *
     * @param string $param Parameter description
     * @return bool Return description
     */
    public function example_method($param) {
        // Implementation
        return true;
    }
}
```

### Key Guidelines

- **Naming Conventions** - Use descriptive names for variables, functions, and classes
- **Documentation** - Add PHPDoc comments for all classes and methods
- **Error Handling** - Use try-catch blocks and proper error logging
- **Security** - Sanitize inputs, escape outputs, use nonces
- **WordPress Integration** - Use WordPress functions and hooks properly

### File Organization

```
chrono-forge/
├── src/                    # Modern architecture (PSR-4)
│   ├── Core/              # Core classes
│   └── Services/          # Service classes
├── includes/              # Legacy architecture
├── admin/                 # Admin interface
├── public/                # Public interface
├── assets/                # CSS, JS, images
└── languages/             # Translation files
```

## 🧪 Testing Guidelines

### Manual Testing

1. **Plugin Activation** - Test activation/deactivation
2. **Core Features** - Test booking, services, employees
3. **Admin Interface** - Test all admin pages and functions
4. **Frontend** - Test all shortcodes and public features
5. **Compatibility** - Test with different WordPress/PHP versions

### Automated Testing

```bash
# Run syntax checks
find . -name "*.php" -exec php -l {} \;

# Run tests (when implemented)
composer test

# Check coding standards
composer phpcs
```

### Test Checklist

- [ ] Plugin activates without errors
- [ ] All admin pages load correctly
- [ ] Shortcodes render properly
- [ ] Database tables are created
- [ ] No PHP errors in debug log
- [ ] Compatible with latest WordPress

## 📤 Submitting Changes

### Pull Request Process

1. **Update documentation** - Update README.md if needed
2. **Add tests** - Include tests for new functionality
3. **Update changelog** - Add entry to CHANGELOG.md
4. **Check compatibility** - Ensure WordPress/PHP compatibility
5. **Submit PR** - Use clear title and description

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Performance improvement

## Testing
- [ ] Manual testing completed
- [ ] No new PHP errors
- [ ] Compatible with WordPress X.X
- [ ] All existing functionality works

## Checklist
- [ ] Code follows project standards
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Changelog updated
```

## 🐛 Reporting Issues

### Bug Reports

When reporting bugs, please include:

1. **WordPress Version** - Your WordPress version
2. **PHP Version** - Your PHP version
3. **Plugin Version** - ChronoForge version
4. **Steps to Reproduce** - Clear reproduction steps
5. **Expected Behavior** - What should happen
6. **Actual Behavior** - What actually happens
7. **Error Messages** - Any error messages or logs
8. **Screenshots** - Visual evidence if applicable

### Feature Requests

For feature requests, please include:

1. **Use Case** - Why is this feature needed?
2. **Proposed Solution** - How should it work?
3. **Alternatives** - Other solutions considered
4. **Additional Context** - Any other relevant information

## 🌍 Translation

Help translate ChronoForge into your language:

1. **Copy template** - Use `languages/chrono-forge.pot`
2. **Translate strings** - Create `.po` file for your language
3. **Compile translation** - Generate `.mo` file
4. **Test translation** - Verify in WordPress
5. **Submit translation** - Create pull request

## 📞 Getting Help

If you need help with contributing:

- **GitHub Discussions** - Ask questions and discuss ideas
- **GitHub Issues** - Report bugs or request features
- **Documentation** - Check README.md and ARCHITECTURE.md

## 🎉 Recognition

Contributors will be recognized in:

- **CHANGELOG.md** - Credit for contributions
- **GitHub Contributors** - Automatic GitHub recognition
- **Plugin Credits** - Recognition in plugin about page

---

Thank you for contributing to ChronoForge! Your contributions help make this plugin better for the entire WordPress community.
