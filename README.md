# Masri Deployer

Automated GitHub Actions workflows generator for Laravel projects deployed to Uberspace.

---

## 🚀 Installation (VCS)

To install this package in your production project via GitHub:

1. Add the custom repository to your project's `composer.json`:
   ```json
   "repositories": [
       {
           "type": "vcs",
           "url": "https://github.com/Masri-Programmer/masri-deployer"
       }
   ]
   ```

2. Require the package:
   ```bash
   composer require masri/deployer:dev-main
   ```
   *(or use `composer require masri/deployer:dev-main --ignore-platform-req=php` if your local PHP version differs)*

---

## 🛠️ Testing Locally in a New Project

To test or develop this package locally using a local path instead of pushing to GitHub:

1. Add a path repository referencing your local clone in the new project's `composer.json`:
   ```json
   "repositories": [
       {
           "type": "path",
           "url": "C:/Users/super/Desktop/masri-deployer",
           "options": {
               "symlink": true
           }
       }
   ]
   ```

2. Install the package:
   ```bash
   composer require masri/deployer
   ```

---

## 🚢 Usage

Initialize the deployment workflows by running:

```bash
php artisan deploy:init
```

The command will:
1. Ask questions to configure staging and production environments.
2. Generate GitHub Actions workflows:
   * `.github/workflows/deploy-staging.yml`
   * `.github/workflows/deploy.yml`
3. Scan for and clean up legacy shell deployment scripts (`ship.sh`, `deploy.sh`, `deploy_backup.sh`).
