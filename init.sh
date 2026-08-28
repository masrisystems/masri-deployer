#!/usr/bin/env bash
# ==============================================================================
# Masri Deployer — Standalone Project Initializer
# Run anywhere: curl -fsSL https://raw.githubusercontent.com/masrisystems/masri-deployer/main/init.sh | bash
# ==============================================================================

set -e

REPO_RAW="https://raw.githubusercontent.com/masrisystems/masri-deployer/main"
TARGET_DIR=$(pwd)
DEFAULT_NAME=$(basename "$TARGET_DIR")

echo "================================================="
echo "   🚀 Masri Deployer — Standalone Initializer   "
echo "================================================="

read -p "Application Name [$DEFAULT_NAME]: " APP_NAME
APP_NAME="${APP_NAME:-$DEFAULT_NAME}"

read -p "Production Domain (e.g. masrisystems.com): " APP_DOMAIN
APP_DOMAIN="${APP_DOMAIN:-${APP_NAME,,}.com}"

read -p "Production SSH User [masri]: " PROD_USER
PROD_USER="${PROD_USER:-masri}"

read -p "Production SSH Host [gienah.uberspace.de]: " PROD_HOST
PROD_HOST="${PROD_HOST:-gienah.uberspace.de}"

read -p "Production Remote Path [/var/www/virtual/$PROD_USER/$DEFAULT_NAME]: " PROD_APP_PATH
PROD_APP_PATH="${PROD_APP_PATH:-/var/www/virtual/$PROD_USER/$DEFAULT_NAME}"

read -p "Production Git Branch [main]: " PROD_BRANCH
PROD_BRANCH="${PROD_BRANCH:-main}"

read -p "SSR / Process Name (Optional, e.g. $DEFAULT_NAME-ssr): " SSR_PROCESS
read -p "Process Manager (systemd / supervisor / pm2 / none) [systemd]: " PROCESS_MANAGER
PROCESS_MANAGER="${PROCESS_MANAGER:-systemd}"

echo ""
echo "📦 Downloading and configuring deployment suite..."

# Fetch Stubs
curl -fsSL "$REPO_RAW/src/Stubs/ship.stub" -o ship.sh.tmp
curl -fsSL "$REPO_RAW/src/Stubs/deploy.stub" -o deploy.sh.tmp
curl -fsSL "$REPO_RAW/src/Stubs/deploy.config.json.stub" -o deploy.config.json.tmp
curl -fsSL "$REPO_RAW/src/Stubs/deploy-production.stub" -o deploy-production.yml.tmp

replace_vars() {
    local file="$1"
    sed -i.bak \
        -e "s|{{APP_NAME}}|$APP_NAME|g" \
        -e "s|{{APP_DOMAIN}}|$APP_DOMAIN|g" \
        -e "s|{{PROD_USER}}|$PROD_USER|g" \
        -e "s|{{PROD_HOST}}|$PROD_HOST|g" \
        -e "s|{{PROD_APP_PATH}}|$PROD_APP_PATH|g" \
        -e "s|{{PROD_BRANCH}}|$PROD_BRANCH|g" \
        -e "s|{{PROD_SSH_KEY_SECRET}}|UBERSPACE_SSH_KEY|g" \
        -e "s|{{SSR_PROCESS}}|$SSR_PROCESS|g" \
        -e "s|{{QUEUE_PROCESS}}||g" \
        -e "s|{{PROCESS_MANAGER}}|$PROCESS_MANAGER|g" \
        "$file" && rm -f "$file.bak"
}

replace_vars ship.sh.tmp
replace_vars deploy.sh.tmp
replace_vars deploy.config.json.tmp
replace_vars deploy-production.yml.tmp

mv ship.sh.tmp ship.sh
mv deploy.sh.tmp deploy.sh
mv deploy.config.json.tmp deploy.config.json

chmod +x ship.sh deploy.sh

mkdir -p .github/workflows
mv deploy-production.yml.tmp .github/workflows/deploy.yml

echo ""
echo "================================================="
echo "   ✅ Deployment Suite Ready for $APP_NAME!      "
echo "================================================="
echo "• Local Fast Ship:    ./ship.sh"
echo "• Check Remote State: ./ship.sh status"
echo "• Remote Executor:    deploy.sh"
echo "• GitHub Actions CI:  .github/workflows/deploy.yml"
echo "• Configuration:      deploy.config.json"
echo "================================================="
