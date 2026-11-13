# 🚀 Masri Deployer

Universal deployment orchestrator and GitHub Actions CI/CD generator for **Laravel (SSR / SPA / Blade)**, **Node.js**, and **Static / PHP** projects deployed to **Uberspace** or any **Linux VPS**.

---

## ⚡ What Masri Deployer Builds in Your Project

| Component | File | Purpose |
| :--- | :--- | :--- |
| **Local Fast Ship** | `./ship.sh` | One-command local build, test runner, zero-downtime remote asset staging, atomic swap, and automated failure rollback. |
| **Remote Executor** | `deploy.sh` | Remote server deploy runner (git sync, composer, database migrations, cache optimization, systemd/supervisor/pm2 restarts, and multi-endpoint health checks). |
| **CI/CD Pipeline** | `.github/workflows/deploy.yml` | Fully automated GitHub Actions workflow triggered on push to `main` (with optional staging workflow). |
| **Single Config Source** | `deploy.config.json` | Centralized server, user, host, path, process manager, and domain configuration. |

---

## 📦 Installation & Setup

### Option A: Laravel Projects (Composer)

1. Add the repository to your `composer.json`:
   ```json
   "repositories": [
       {
           "type": "vcs",
           "url": "https://github.com/masrisystems/masri-deployer"
       }
   ]
   ```

2. Require the package:
   ```bash
   composer require masrisystems/masri-deployer:dev-main
   ```

3. Run the interactive initialization wizard:
   ```bash
   php artisan deploy:init
   ```

---

### Option B: Standalone / Non-Laravel Projects (Node, Vue, React, Static)

Run the universal 1-line installer in your project root:

```bash
curl -fsSL https://raw.githubusercontent.com/masrisystems/masri-deployer/main/init.sh | bash
```

---

## 🛠️ CLI Commands & Usage

### 1. Fast Local Deployment
```bash
./ship.sh
```
* Runs code linter (`pint`) & automated tests.
* Prompts for optional semantic version bump (`npm version`).
* Compiles production client & SSR assets locally with maximum memory limit.
* Stages assets into an isolated `.deploy/<timestamp>` folder on the server.
* Performs **zero-downtime atomic swap** (`public/build` & `bootstrap/ssr`).
* Restarts remote workers and verifies HTTP health status.
* **Auto-Rollback Guard:** Automatically restores previous build and recovers application if any step fails.

### 2. Remote Server Status Check
```bash
./ship.sh status
# or
php artisan deploy:status
```
* Inspects remote process managers (`systemctl --user`, `supervisorctl`, `pm2`).
* Checks Laravel maintenance mode status.
* Reports remote build asset disk usage.

### 3. Pre-Flight Connectivity Check (Laravel)
```bash
php artisan deploy:check
```
* Verifies local CLI prerequisites (Git, Composer, NPM, SSH).
* Tests SSH connection to remote host and confirms remote app directory existence.

---

## 🌐 Supported Stacks & Server Environments

* **Frameworks:** Laravel 11/12 (Vue/React Inertia SSR, SPA, Blade, Filament, Livewire), Node.js, Next.js, Nuxt, Astro, Vite, Static HTML/PHP.
* **Process Managers:** Systemd (`systemctl --user`), Supervisor (`supervisorctl`), PM2 (`pm2`).
* **Hosting:** Uberspace 7/8, Ubuntu / Debian / RHEL Linux VPS, Hetzner, DigitalOcean, AWS EC2.

---

## 📜 License

[Masri Systems Non-Commercial Source-Available License (MS-NC-1.0)](LICENSE) © [Masri Systems](https://masrisystems.com) — personal and academic use only; business and commercial environments prohibited without a commercial license (`hallo@masrisystems.com`).
