# ChronoForge Project Testing Suite

This directory contains testing and diagnostic tools for the ChronoForge WordPress plugin project. These files are designed to help developers verify plugin functionality, diagnose issues, and ensure proper implementation during development and testing phases.

## 📁 Test Files Overview

### 1. `chrono-forge-minimal.php`
**Purpose**: Minimal WordPress plugin for basic functionality testing  
**Type**: WordPress Plugin (Minimal Version)  
**Environment**: WordPress installation

**Features**:
- Minimal plugin structure for testing WordPress integration
- Basic admin menu and page
- System information display
- Simple activation/deactivation hooks
- Error logging for debugging

**Usage**:
1. Copy to WordPress plugins directory
2. Activate through WordPress admin
3. Check admin menu for "CF Minimal"

**Use Case**: Test WordPress environment compatibility before deploying full plugin

---

### 2. `emergency-fix.php`
**Purpose**: Emergency repair tool for critical plugin issues  
**Type**: Emergency Recovery Tool  
**Environment**: Command line (outside WordPress)

**Features**:
- Plugin disable/enable functionality
- Safe mode creation
- File permission fixes
- Interactive menu system
- Comprehensive error handling

**Usage**:
```bash
php project-tests/emergency-fix.php
```

**Available Fixes**:
1. Disable plugin (rename main file)
2. Enable plugin (restore main file)
3. Create safe mode version
4. Fix file permissions
5. Run all fixes

**Use Case**: When plugin causes critical WordPress errors and needs immediate intervention

---

### 3. `syntax-check-simple.php`
**Purpose**: Basic PHP syntax validation for all plugin files  
**Type**: Syntax Checker  
**Environment**: Command line

**Features**:
- Recursive PHP file discovery
- PHP lint checking (`php -l`)
- Clear pass/fail reporting
- File count statistics
- Error details for failed files

**Usage**:
```bash
php project-tests/syntax-check-simple.php
```

**Output**: Console report with syntax validation results

**Use Case**: Quick syntax verification before deployment or after code changes

---

### 4. `test-all-syntax.php`
**Purpose**: Comprehensive syntax and code quality testing  
**Type**: Advanced Syntax Checker  
**Environment**: Command line

**Features**:
- PHP syntax validation
- Common issue detection (unmatched braces, missing semicolons)
- Detailed error reporting
- File-by-file analysis
- Summary statistics

**Usage**:
```bash
php project-tests/test-all-syntax.php
```

**Checks Performed**:
- PHP opening tags
- Brace matching
- Parentheses matching
- Basic syntax patterns

**Use Case**: Thorough code quality assessment before major releases

---

### 5. `test-plugin-fix.php`
**Purpose**: Validation of specific plugin architecture fixes  
**Type**: Integration Tester  
**Environment**: Command line

**Features**:
- Autoloader functionality testing
- Container dependency injection testing
- Main plugin file validation
- Global functions verification
- Backward compatibility checks
- File structure validation

**Usage**:
```bash
php project-tests/test-plugin-fix.php
```

**Test Categories**:
1. File structure verification
2. Autoloader functionality
3. Container operations
4. Main plugin file modifications
5. Global functions availability
6. Backward compatibility

**Use Case**: Verify that architectural improvements work correctly

---

### 6. `test-syntax-fixes.php`
**Purpose**: Specific testing of syntax error fixes  
**Type**: Fix Validation Tool  
**Environment**: Command line

**Features**:
- Container.php ArrayAccess implementation check
- Plugin.php WordPress function checks
- Main file global variable fixes
- Functions.php error handling validation
- Autoloader exception handling
- Core files syntax validation

**Usage**:
```bash
php project-tests/test-syntax-fixes.php
```

**Specific Fixes Tested**:
- ArrayAccess interface implementation
- WordPress function existence checks
- Global variable handling improvements
- Exception handling additions
- Function existence validations

**Use Case**: Ensure specific bug fixes resolve original issues without introducing new problems

---

### 7. `check-plugin-status.ps1`
**Purpose**: PowerShell script for Windows environment status checking  
**Type**: Status Checker (Windows)  
**Environment**: PowerShell

**Features**:
- File existence verification
- Directory structure validation
- File size reporting
- PHP syntax checking (if PHP available)
- Comprehensive status summary
- Color-coded output

**Usage**:
```powershell
.\project-tests\check-plugin-status.ps1
```

**Checks Performed**:
- Core files presence and size
- Core classes availability
- Directory structure integrity
- Legacy files compatibility
- PHP syntax validation

**Use Case**: Quick status overview in Windows development environments

## 🚀 Usage Scenarios

### Development Workflow

1. **Initial Setup Verification**:
   ```bash
   php project-tests/syntax-check-simple.php
   ```

2. **Architecture Testing**:
   ```bash
   php project-tests/test-plugin-fix.php
   ```

3. **Comprehensive Quality Check**:
   ```bash
   php project-tests/test-all-syntax.php
   ```

4. **Fix Validation**:
   ```bash
   php project-tests/test-syntax-fixes.php
   ```

### Emergency Situations

1. **Plugin Causing Critical Errors**:
   ```bash
   php project-tests/emergency-fix.php
   # Choose option 1 to disable plugin
   ```

2. **WordPress Site Recovery**:
   ```bash
   php project-tests/emergency-fix.php
   # Choose option 3 to create safe mode
   ```

### Testing in WordPress

1. **Minimal Functionality Test**:
   - Use `chrono-forge-minimal.php` as a test plugin
   - Verify WordPress integration works

2. **Windows Environment**:
   ```powershell
   .\project-tests\check-plugin-status.ps1
   ```

## 🔧 System Requirements

### General Requirements
- **PHP**: 7.4 or higher
- **Operating System**: Windows, Linux, macOS
- **Permissions**: Read access to plugin files

### Specific Requirements

#### For WordPress Testing
- **WordPress**: 5.0 or higher
- **PHP Extensions**: mysqli, json, curl
- **File Permissions**: 644 for files, 755 for directories

#### For PowerShell Script
- **PowerShell**: 5.1 or higher
- **Windows**: 10 or higher (recommended)

## 📊 Understanding Test Results

### Success Indicators
- ✅ **Green checkmarks**: Tests passed successfully
- **File sizes displayed**: Files exist and are readable
- **"All tests passed"**: Complete success

### Warning Indicators
- ⚠️ **Yellow warnings**: Non-critical issues
- **"Some warnings found"**: Minor issues that don't prevent functionality

### Error Indicators
- ❌ **Red X marks**: Critical failures
- **"Tests failed"**: Issues that need immediate attention
- **Exception messages**: Specific error details

## 🐛 Troubleshooting

### Common Issues

#### "Plugin directory not found"
**Solution**: Ensure you're running tests from the correct directory

#### "PHP not found"
**Solution**: Add PHP to system PATH or specify full PHP path

#### "Permission denied"
**Solution**: Check file permissions and run with appropriate privileges

#### "Class not found"
**Solution**: Verify autoloader exists and namespace is correct

### Debug Tips

1. **Enable verbose output** by modifying test scripts
2. **Check file permissions** using system tools
3. **Verify PHP version** compatibility
4. **Review error logs** for detailed information

## 📝 Adding New Tests

When creating new test files:

1. **Follow naming convention**: `test-[purpose].php` or `check-[aspect].php`
2. **Include proper headers** with purpose and usage instructions
3. **Add error handling** for robust testing
4. **Provide clear output** with consistent formatting
5. **Update this README** with new test descriptions

## 🔒 Security Considerations

- **Standalone scripts**: Include WordPress context checks
- **File access**: Validate paths to prevent directory traversal
- **Sensitive data**: Avoid exposing configuration details
- **Permissions**: Use appropriate file permissions

## 📚 Related Documentation

- **Plugin Documentation**: `../chrono-forge/ARCHITECTURE.md`
- **Main README**: `../README.md`
- **Plugin Tests**: `../chrono-forge/tests/README.md`

## 🤝 Contributing

When contributing to the test suite:

1. **Test thoroughly** on multiple environments
2. **Follow existing patterns** and conventions
3. **Document new tests** in this README
4. **Include error handling** and validation
5. **Provide clear usage instructions**

---

**Note**: These test files are for development and diagnostic purposes only. They should not be deployed to production WordPress installations. Always test in a safe development environment first.
