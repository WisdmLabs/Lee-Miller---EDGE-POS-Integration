# Repository Paths for Git

## Folders to Upload to Git Repository

### Primary Folders
```
wp-content/
├── plugins/
│   └── wdm-edge-integration/     # Custom plugin (WDM EDGE Integration)
└── themes/                       # Themes directory (using .gitkeep)
```

### Development Documentation
```
.cursor/                          # Development documentation and specifications
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
│   │   └── wdm-edge-integration/  # Custom plugin files
│   └── themes/
│       └── .gitkeep               # Keep themes directory in git
├── .cursor/                       # Development documentation
├── .gitignore                     # Git ignore rules
└── README.md                      # Project overview
```

## Custom Plugins Being Developed
- **WDM EDGE Integration Plugin:** `wp-content/plugins/wdm-edge-integration/`
  - Complete plugin with all modules
  - Admin interface and functionality
  - SFTP integration and data synchronization

## Custom Themes Being Developed  
- **Current Status:** No custom themes being developed
- **Directory:** `wp-content/themes/` (maintained with .gitkeep)
- **Purpose:** Future theme development

## Git Workflow
1. **Default Branch:** `dev` (active development)
2. **Feature Development:** Create feature branches from `dev`
3. **Testing:** Test changes locally before committing
4. **Commit:** Use descriptive commit messages
5. **Push:** Push to `dev` branch
6. **Release:** Merge `dev` to `main` for production releases

## Files to Track
- **Plugin Files:** All files in `wp-content/plugins/wdm-edge-integration/`
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