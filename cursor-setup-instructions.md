# WordPress .cursor Folder Setup Instructions

This document provides step-by-step instructions for creating a standardized `.cursor` folder structure for WordPress development projects. Follow the appropriate scenario based on your current project setup.

---

## Quick Start Guide

**Choose the scenario that matches your current project setup:**

1. **Scenario 1:** You have a complete WordPress project with `wp-content` directory
2. **Scenario 2:** You have existing plugin/theme files you've developed
3. **Scenario 3:** You're starting fresh and need to plan development

---

## Scenario 1: Existing WordPress Project Setup

**Use this when:** You have a complete WordPress project with `wp-content` directory containing plugins and themes.

### Step 1: Analyze Your Project Structure
First, examine your existing project to understand what you're working with:

```bash
# Check if you have wp-content directory
ls -la wp-content/

# List existing plugins
ls -la wp-content/plugins/

# List existing themes  
ls -la wp-content/themes/
```

### Step 2: Create the .cursor folder structure
```bash
mkdir -p .cursor/{docs,specs/{plugins,themes},testing/{unit-tests,e2e-tests,manual},git}
```

### Step 3: Create base documentation files

#### `.cursor/docs/requirements.md`
```markdown
# Project Requirements

## Project Overview
[PROJECT_NAME] - [Brief description of what this project does]

## What We're Building
- **Primary Goal:** [Main objective]
- **Target Users:** [Who will use this]
- **Key Functionality:** [Core features needed]

## Key Features Needed
- [ ] Feature 1: [Description]
- [ ] Feature 2: [Description] 
- [ ] Feature 3: [Description]
- [ ] Feature 4: [Description]

## WordPress Requirements
- **WordPress Version:** [Minimum version required]
- **PHP Version:** [Minimum PHP version]
- **Required Plugins:** [List any dependencies]
- **Theme Requirements:** [Any theme-specific needs]

## Technical Requirements
- **Browser Support:** [List supported browsers]
- **Mobile Responsiveness:** [Yes/No and requirements]
- **Performance Goals:** [Page load times, etc.]
- **SEO Requirements:** [Any specific SEO needs]

## Completion Criteria
- [ ] All features working as specified
- [ ] Cross-browser compatibility tested
- [ ] Mobile responsiveness verified
- [ ] Performance requirements met
- [ ] Security measures implemented
- [ ] Documentation complete
- [ ] Client approval received

## Timeline
- **Project Start:** [DATE]
- **Target Completion:** [DATE]
- **Key Milestones:**
  - [DATE] - [Milestone 1]
  - [DATE] - [Milestone 2]
  - [DATE] - [Milestone 3]
```

#### `.cursor/docs/progress.md`
```markdown
# Development Progress Log

## Project Information
- **Project:** [PROJECT_NAME]
- **Started:** [DATE]
- **Current Developer:** [YOUR_NAME]
- **Last Updated:** [DATE]
- **Overall Progress:** [X%]

## Current Sprint/Week Goals
- [ ] [Goal 1]
- [ ] [Goal 2]
- [ ] [Goal 3]

## This Week's Progress

### Completed ✅
- [Completed task 1]
- [Completed task 2]

### Currently Working On 🔄
- [Current task] - [X% complete]
- [Another ongoing task] - [Status details]

### Next Up ⏳
- [Next planned task]
- [Another upcoming task]

## Recent Issues/Blockers ❌
- **Issue:** [Problem description]
  - **Status:** [Investigating/Blocked/Resolved]
  - **Solution:** [How it was or will be resolved]

## Key Decisions Made
- [Important decision 1] - **Reasoning:** [Why this was chosen]
- [Important decision 2] - **Impact:** [What this affects]

## Time Tracking
- **This Week:** [X hours]
- **Total Project Time:** [X hours]
- **Estimated Remaining:** [X hours]

## Notes for Next Developer
- [Important context or gotcha]
- [Key learning or insight]
- [Process or setup note]
```

#### `.cursor/docs/setup.md`
```markdown
# Project Setup Guide

## Prerequisites
- WordPress 6.0+ (already installed)
- PHP 8.0+
- Node.js 16+ (if using build tools)
- Composer (if using PHP dependencies)
- Local development environment (XAMPP/MAMP/Local by Flywheel/etc.)
- Git repositories already set up and configured

## Initial Setup Steps

### 1. Project Setup
```bash
# Navigate to your existing project directory
cd [PROJECT_DIRECTORY]
```

### 2. WordPress Setup
- Ensure WordPress core files are already present
- Verify database and wp-config.php are configured
- Confirm site URLs are properly set

### 3. Custom Plugin Development Setup
- Navigate to wp-content/plugins/
- Create directories for custom plugins being developed (see git/repository-paths.md for list)
- Set up development environment for plugin coding

### 4. Custom Theme Development Setup  
- Navigate to wp-content/themes/
- Create directories for custom themes being developed (see git/repository-paths.md for list)
- Set up development environment for theme coding

### 5. Development Environment
- Set up local server environment
- Configure debugging (WP_DEBUG = true)
- Install development tools as needed

## File Permissions
Set appropriate permissions:
```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 wp-config.php
```

## Testing Setup
- Verify WordPress loads without errors
- Confirm development tools are working
- Test that custom code can be written and executed
- Verify existing git repositories are accessible

## Troubleshooting Common Issues
- **White Screen:** Check error logs, enable WP_DEBUG
- **Development Environment:** Ensure proper file permissions for writing
- **Code Changes Not Reflecting:** Check caching, hard refresh browser
- **Database Connection:** Verify wp-config.php database settings
- **Git Issues:** Verify repository access and permissions
```

### Step 4: Document existing plugins and themes
For each existing plugin/theme in your `wp-content` directory:

1. **Create spec folders:**
   ```bash
   # For each plugin
   mkdir -p .cursor/specs/plugins/[EXISTING_PLUGIN_NAME]/modules
   
   # For each theme
   mkdir -p .cursor/specs/themes/[EXISTING_THEME_NAME]
   ```

2. **Analyze and document existing code:**
   - Read plugin header files to understand purpose
   - Document existing functions and features
   - List WordPress hooks being used
   - Document file structure and dependencies

3. **Create documentation files** following the templates below

### Step 5: Create plugin documentation template

#### `.cursor/specs/plugins/[PLUGIN_NAME]/main.md`
```markdown
# [Plugin Name] - Main Documentation

## Plugin Overview
[Describe what this plugin does based on your analysis]

## Module Structure
[Document the major features/modules this plugin has]

### Modules
- **[Module 1 Name]** → [modules/module-1.md](modules/module-1.md)
  - Purpose: [What this module does]
  - Priority: [High/Medium/Low]
  - Status: [Complete/In Progress/Planned]

- **[Module 2 Name]** → [modules/module-2.md](modules/module-2.md)
  - Purpose: [What this module does]
  - Priority: [High/Medium/Low]
  - Status: [Complete/In Progress/Planned]

## Plugin Architecture
[Document the overall plugin structure and design]

## Key Functions
[List main functions that exist in the plugin]

## WordPress Hooks Used
[Document which hooks/filters are being used]

## Database Changes
[Document any custom tables or fields]

## Dependencies
- **WordPress Core:** [Version requirements]
- **Other Plugins:** [Plugin dependencies]
- **PHP Extensions:** [Required PHP extensions]
```

#### `.cursor/specs/plugins/[PLUGIN_NAME]/modules/[MODULE_NAME].md`
```markdown
# [Module Name] - Module Documentation

## Module Overview
[Brief description of what this module does]

## Purpose
[Detailed explanation of the module's purpose and functionality]

## Priority
[High/Medium/Low] - [Reasoning for priority level]

## Status
[Complete/In Progress/Planned] - [Current development status]

## Features
- [x] Feature 1: [Description] - [Status]
- [ ] Feature 2: [Description] - [Status]
- [ ] Feature 3: [Description] - [Status]

## Technical Requirements
- **WordPress Hooks Used:**
  - `init` - [Purpose]
  - `wp_enqueue_scripts` - [Purpose]
  - `admin_menu` - [Purpose]

- **Database Changes:**
  - [Table/Field changes needed]
  - [Data structure requirements]

- **File Dependencies:**
  - [List of files this module depends on]
  - [External libraries or resources]

## Implementation Details
### Core Functions
```php
// Main module function
function module_name_init() {
    // Implementation details
}

// Admin interface function
function module_name_admin_page() {
    // Implementation details
}
```

### Hooks and Filters
- **Actions:**
  - `wp_loaded` → `module_name_init()`
  - `admin_menu` → `module_name_add_menu()`

- **Filters:**
  - `the_content` → `module_name_filter_content()`

## User Interface
- **Admin Pages:** [List admin pages this module creates]
- **Frontend Elements:** [List frontend elements this module adds]
- **Settings:** [Configuration options this module provides]

## Testing Requirements
- [ ] Unit tests for core functions
- [ ] Integration tests for WordPress hooks
- [ ] User interface testing
- [ ] Database operation testing

## Security Considerations
- [ ] Input sanitization implemented
- [ ] Output escaping implemented
- [ ] Capability checks added
- [ ] Nonce verification included

## Performance Considerations
- [ ] Database queries optimized
- [ ] Caching implemented where appropriate
- [ ] Asset loading optimized

## Dependencies
- **WordPress Core:** [Version requirements]
- **Other Plugins:** [Plugin dependencies]
- **PHP Extensions:** [Required PHP extensions]

## Notes
[Any additional notes, gotchas, or important information]
```

### Step 6: Create theme documentation template

#### `.cursor/specs/themes/[THEME_NAME]/overview.md`
```markdown
# [Theme Name] Overview

## Theme Purpose
[Describe what this theme provides]

## Features
[List features that exist in the theme]

## Custom Files
[List custom template files and their purposes]

## Theme Customizations
[Document any custom functions, hooks, or modifications]

## Template Hierarchy
[Document custom templates and their usage]

## Development Status
[Complete/In Progress/Planned and reasoning]
```

### Step 7: Create git repository documentation

#### `.cursor/git/repository-paths.md`
```markdown
# Repository Paths for Git

## Folders to Upload to Git Repository

### Primary Folders
```
wp-content/
├── plugins/
│   └── [plugin-name]/     # Custom plugin
└── themes/                # Themes directory (using .gitkeep)
```

### Development Documentation
```
.cursor/                   # Development documentation and specifications
```

## Repository Configuration

### Default Branch
- **Default Branch:** `dev`
- **Main Branch:** `main` (for production releases)
- **Development Branch:** `dev` (for active development)

### Git Structure
```
Repository Root/
├── wp-content/
│   ├── plugins/
│   │   └── [plugin-name]/  # Custom plugin files
│   └── themes/
│       └── .gitkeep        # Keep themes directory in git
├── .cursor/                # Development documentation
├── .gitignore              # Git ignore rules
└── README.md               # Project overview
```

## Custom Plugins Being Developed
- **[Plugin Name]:** `wp-content/plugins/[plugin-name]/`
  - [Brief description of plugin functionality]

## Custom Themes Being Developed
- **Current Status:** [Status of theme development]
- **Directory:** `wp-content/themes/` (maintained with .gitkeep)
- **Purpose:** [Purpose of theme development]

## Git Workflow
1. **Default Branch:** `dev` (active development)
2. **Feature Development:** Create feature branches from `dev`
3. **Testing:** Test changes locally before committing
4. **Commit:** Use descriptive commit messages
5. **Push:** Push to `dev` branch
6. **Release:** Merge `dev` to `main` for production releases

## Files to Track
- **Plugin Files:** All files in `wp-content/plugins/[plugin-name]/`
- **Themes Directory:** `wp-content/themes/` (with .gitkeep)
- **Documentation:** Complete `.cursor/` folder
- **Configuration:** `.gitignore`, `README.md`

## Files to Ignore
- WordPress core files (wp-admin/, wp-includes/, wp-config.php)
- Database files and backups
- Upload directories (wp-content/uploads/)
- Temporary files and logs
- IDE-specific files (.vscode/, .idea/)
- Node modules and build artifacts
```

### Step 8: Create testing documentation

#### `.cursor/testing/manual-tests.md`
```markdown
# Manual Testing Checklist

## Plugin Development Testing
[For each custom plugin, create test cases]
- [ ] [Plugin Name] basic structure created
- [ ] [Plugin Name] activates without errors
- [ ] [Plugin Name] core functionality works
- [ ] [Plugin Name] admin interface functions properly
- [ ] [Plugin Name] frontend features work as expected

## Theme Development Testing  
[For each custom theme, create test cases]
- [ ] [Theme Name] basic files created
- [ ] [Theme Name] activates properly
- [ ] All template files work correctly
- [ ] Mobile responsiveness implemented
- [ ] Custom features function properly

## Development Environment Testing
- [ ] WordPress debug mode working
- [ ] No PHP errors during development
- [ ] File changes reflect immediately
- [ ] Database operations work correctly

## Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)  
- [ ] Safari (latest)
- [ ] Edge (latest)

## WordPress Development Standards
- [ ] Code follows WordPress coding standards
- [ ] Proper sanitization and escaping used
- [ ] No security vulnerabilities introduced
- [ ] Performance optimized
```

#### `.cursor/testing/unit-tests.md`
```markdown
# Unit Testing Instructions

## Overview
Unit tests should test individual components and functions in isolation. These tests will be executed using Playwright or browser MCP for automated testing.

## Test Environment Setup
- **Testing Framework:** Playwright with PHPUnit integration
- **Browser:** Headless Chrome/Firefox for UI component testing
- **Database:** Test database with sample data
- **WordPress:** Test WordPress installation with WooCommerce
- **Plugin:** [Plugin Name] activated

## Module-Specific Test Cases

### [Module 1] Tests
- **Test Function 1:** [Description of what to test]
- **Test Function 2:** [Description of what to test]
- **Test Error Handling:** [Description of error scenarios]

### [Module 2] Tests
- **Test Function 1:** [Description of what to test]
- **Test Function 2:** [Description of what to test]
- **Test Error Handling:** [Description of error scenarios]

## Test Data Requirements
- **Sample Data Sets:** [Description of test data needed]
- **Test Credentials:** [Description of test credentials needed]

## Test Execution Instructions
1. **Setup Test Environment:** [Steps to set up testing environment]
2. **Run Module Tests:** [Steps to execute tests]
3. **Verify Results:** [Steps to validate test results]

## Expected Test Outcomes
- **Success Criteria:** [What defines successful tests]
- **Failure Handling:** [How to handle test failures]
```

#### `.cursor/testing/e2e-tests.md`
```markdown
# End-to-End Testing Instructions

## Overview
End-to-end tests should test complete user workflows and system integration. These tests will be executed using Playwright or browser MCP for automated browser-based testing.

## Test Environment Setup
- **Testing Framework:** Playwright with browser automation
- **Browser:** Chrome/Firefox for full browser testing
- **Database:** Production-like database with sample data
- **WordPress:** Full WordPress installation with WooCommerce
- **Plugin:** [Plugin Name] fully configured

## Complete Workflow Test Scenarios

### 1. Plugin Installation and Setup Workflow
- **Test Plugin Installation:** Verify plugin installs without errors
- **Test Plugin Activation:** Verify plugin activates successfully
- **Test Admin Menu Creation:** Verify admin menu appears in WordPress admin
- **Test Settings Page Access:** Verify settings page is accessible

### 2. [Feature 1] Workflow
- **Test [Feature 1] Process:**
  - Navigate to [feature] page
  - Select [operation]
  - Configure [settings]
  - Execute [process]
  - Verify [expected outcome]
  - Check [logs/notifications]

### 3. [Feature 2] Workflow
- **Test [Feature 2] Process:**
  - [Step-by-step workflow description]

## Test Data Requirements
- **Production-Like Data Sets:** [Description of test data needed]
- **Test Environment Setup:** [Description of test environment]

## Test Execution Instructions
1. **Setup Test Environment:** [Steps to set up complete test environment]
2. **Execute Workflow Tests:** [Steps to run workflow scenarios]
3. **Validate Results:** [Steps to verify expected outcomes]

## Expected Test Outcomes
- **Success Criteria:** [What defines successful E2E tests]
- **Failure Scenarios:** [How to handle test failures]
```

### Step 9: Create the main README.md

#### `.cursor/README.md`
```markdown
# [Project Name] - Development Documentation

## Project Overview
[Brief description of the WordPress project and its purpose]

## Quick Start
1. **Prerequisites:** Ensure WordPress 6.0+ is installed with PHP 8.0+
2. **Setup:** Follow the setup guide in [docs/setup.md](docs/setup.md)
3. **Development:** Review requirements in [docs/requirements.md](docs/requirements.md)
4. **Progress:** Track development in [docs/progress.md](docs/progress.md)

## Project Structure
```
.cursor/
├── docs/                    # Project documentation
│   ├── requirements.md      # Project requirements and goals
│   ├── progress.md          # Development progress tracking
│   └── setup.md            # Setup and installation guide
├── specs/                   # Detailed specifications
│   ├── plugins/            # Plugin development specs
│   │   └── [plugin-name]/
│   │       ├── main.md     # Main plugin documentation
│   │       └── modules/    # Individual module documentation
│   └── themes/             # Theme development specs
│       └── [theme-name]/
│           └── overview.md # Theme documentation
├── testing/                # Testing documentation and scripts
│   ├── manual-tests.md     # Manual testing checklist
│   ├── unit-tests.md       # Unit testing instructions
│   └── e2e-tests.md        # End-to-end testing instructions
└── git/                    # Version control documentation
    └── repository-paths.md # Git repository paths
```

## Development Workflow
1. **Planning:** Document requirements and specifications
2. **Development:** Follow WordPress coding standards
3. **Testing:** Use manual and automated testing procedures
4. **Review:** Code review and quality assurance
5. **Deployment:** Commit and push to existing repositories

## Key Documents
- **[Requirements](docs/requirements.md)** - Project goals and specifications
- **[Progress](docs/progress.md)** - Current development status
- **[Setup Guide](docs/setup.md)** - Environment setup instructions
- **[Testing](testing/manual-tests.md)** - Testing procedures and checklists

## Plugin Architecture
[Brief description of plugin structure and modules]

## Key Features
- **[Feature 1]:** [Brief description]
- **[Feature 2]:** [Brief description]
- **[Feature 3]:** [Brief description]

## Repository Management
- **Custom Plugin:** `wp-content/plugins/[plugin-name]/`
- **Development Documentation:** `.cursor/` folder
- **Version Control:** Follow established git workflow

## Current Status
- **Overall Progress:** [X% complete]
- **Core Functionality:** [Status]
- **Admin Interface:** [Status]
- **Testing:** [Status]

## Contact & Support
- **Developer:** [Your Name]
- **Last Updated:** [Date]
- **Project Status:** [Active/On Hold/Completed]

## Notes
- This documentation is maintained in the `.cursor/` folder
- Update progress.md regularly during development
- Follow WordPress coding standards and security best practices
- All plugin functionality is admin-only (no frontend impact)
- Comprehensive testing framework available for quality assurance
```

---

## Scenario 2: Existing Plugin/Theme Files Setup

**Use this when:** You have plugin and theme files that you've developed/are developing, stored in directories.

### Step 1: Analyze your development directory structure
```bash
# Identify plugins you've developed:
ls -la plugins/
# Identify themes you've developed:
ls -la themes/
```

### Step 2: Create .cursor structure
```bash
mkdir -p .cursor/{docs,specs/{plugins,themes},testing/{unit-tests,e2e-tests,manual},git}
```

### Step 3: Document existing developed plugins
For each plugin directory you've created:

1. **Create plugin spec folder:**
   ```bash
   mkdir -p .cursor/specs/plugins/[YOUR_PLUGIN_NAME]/modules
   ```

2. **Analyze your plugin code** to understand:
   - Plugin purpose (from your header comments)
   - Functions you've written
   - Hooks you've used
   - File structure you've created

3. **Document your development work** following the templates above

4. **Break down into modules** by analyzing your code structure and documenting the features you've built

### Step 4: Document existing developed themes
For each theme directory you've created:

1. **Create theme spec folder:**
   ```bash
   mkdir -p .cursor/specs/themes/[YOUR_THEME_NAME]
   ```

2. **Analyze your theme files:**
   - Check your `style.css` header
   - Document your custom template files
   - List your `functions.php` customizations
   - Document any custom post types or fields you've created

### Step 5: Create documentation files
Follow the same template creation process as Scenario 1, but document what you've already developed.

### Step 6: Create git paths documentation
List the plugin and theme directories you've developed:

```markdown
# Repository Paths for Git

## Custom Plugins Developed
```
plugins/[your-plugin-1]/
plugins/[your-plugin-2]/
```

## Custom Themes Developed  
```
themes/[your-theme-1]/
themes/[your-child-theme]/
```
```

---

## Scenario 3: Fresh Development Setup

**Use this when:** You're starting fresh and need to plan development of new plugins/themes.

### Step 1: Create .cursor structure
```bash
mkdir -p .cursor/{docs,specs/{plugins,themes},testing/{unit-tests,e2e-tests,manual},git}
```

### Step 2: Plan your development
1. **Define project requirements** in `docs/requirements.md`
2. **Plan plugin/theme development** in `specs/` folders
3. **Set up testing framework** in `testing/` folders
4. **Configure git workflow** in `git/repository-paths.md`

### Step 3: Follow templates
Use the templates provided in Scenario 1 to plan your development work.

---

## Implementation Instructions

### For IDE/Development Environment:

1. **Run the appropriate scenario commands** based on your project type
2. **Analyze existing code** if you have plugins/themes to document
3. **Fill in templates** with your project information
4. **Document development progress** as you build features
5. **Update git paths** with actual directory names
6. **Create module breakdowns** based on features you're developing
7. **Track progress** honestly - mark incomplete items clearly

### Key Success Factors:

- **Document what you're building** - be specific about features and functionality
- **Track your development progress** - update progress.md regularly
- **Maintain clear specifications** - this becomes your development roadmap
- **Follow WordPress standards** - ensure code quality and security
- **Test thoroughly** - use both manual and automated testing
- **Keep documentation updated** - this is your knowledge transfer document

### File Structure Created:
```
.cursor/
├── docs/
│   ├── requirements.md
│   ├── progress.md
│   └── setup.md
├── specs/
│   ├── plugins/
│   │   └── [plugin-name]/
│   │       ├── main.md
│   │       └── modules/
│   │           ├── module-1.md
│   │           └── module-2.md
│   └── themes/
│       └── [theme-name]/
│           └── overview.md
├── testing/
│   ├── manual-tests.md
│   ├── unit-tests.md
│   └── e2e-tests.md
├── git/
│   └── repository-paths.md
└── README.md
```

This structure provides comprehensive documentation for WordPress development projects, making it easier to track progress, maintain code quality, and hand over projects to other developers.