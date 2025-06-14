# ChronoForge Tests

This directory contains all test files and debugging scripts for the ChronoForge plugin.

## Test Files

- `activation-test.php` - Tests plugin activation process
- `debug-class-loading.php` - Debugs class loading issues
- `isolated-class-test.php` - Tests individual class loading
- `manual-class-test.php` - Manual class testing utilities
- `simple-load-test.php` - Simple plugin loading tests
- `test-initialization.php` - Tests plugin initialization
- `wordpress-class-test.php` - WordPress-specific class tests

## Usage

These test files are for development and debugging purposes only. They should not be included in production deployments.

To run tests, access them directly via browser or command line:
```
php tests/activation-test.php
```

## Note

All test files have been moved from the main plugin directory to maintain a clean production structure.
