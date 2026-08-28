<?php

namespace Masri\Deployer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitDeployment extends Command
{
    protected $signature = 'deploy:init {--force : Overwrite existing deployment files without prompting}';

    protected $description = 'Generate full deployment suite: ship.sh, deploy.sh, deploy.config.json, and GitHub Actions';

    public function handle()
    {
        $this->info("=================================================");
        $this->info("   🚀 Masri Deployer — Universal Setup Wizard    ");
        $this->info("=================================================");

        // 1. Auto-detect Project Meta
        $baseDir = base_path();
        $defaultName = basename($baseDir);
        $hasPackageJson = File::exists("{$baseDir}/package.json");
        $hasComposer = File::exists("{$baseDir}/composer.json");
        $hasSsr = false;

        if ($hasPackageJson) {
            $pkg = json_decode(File::get("{$baseDir}/package.json"), true) ?: [];
            if (isset($pkg['scripts']['build:ssr']) || File::exists("{$baseDir}/bootstrap/ssr")) {
                $hasSsr = true;
            }
        }

        $projectType = $hasSsr ? 'laravel-ssr' : ($hasComposer ? 'laravel-spa' : 'node-or-static');

        $appName = $this->ask('Application Name', config('app.name') ?: ucfirst($defaultName));
        $appDomain = $this->ask('Production Domain (without https://)', strtolower(str_replace(' ', '', $appName)) . '.com');
        $ssrProcess = $hasSsr ? $this->ask('SSR Service Name (Systemd / Supervisor / PM2)', "{$defaultName}-ssr") : '';
        $queueProcess = $this->ask('Queue Worker Service Name (Leave blank if none)', "{$defaultName}-queue");
        $processManager = $this->choice('Process Manager on Server', ['systemd', 'supervisor', 'pm2', 'none'], 0);

        // 2. Production Environment
        $this->comment("\n--- 🌐 Production Server Settings ---");
        $prodUser = $this->ask('Production SSH User', 'masri');
        $prodHost = $this->ask('Production SSH Host / Server', 'gienah.uberspace.de');
        $prodPath = $this->ask('Production Remote App Path', "/var/www/virtual/{$prodUser}/{$defaultName}");
        $prodBranch = $this->ask('Production Git Branch', 'main');
        $prodKeySecret = $this->ask('GitHub Secret Name for Production SSH Key', 'UBERSPACE_SSH_KEY');

        // 3. Staging Environment (Optional)
        $this->comment("\n--- 🧪 Staging Server Settings ---");
        $configureStaging = $this->confirm('Do you want to configure a staging environment workflow?', false);
        $stagingUser = $prodUser;
        $stagingHost = $prodHost;
        $stagingPath = "/var/www/virtual/{$prodUser}/{$defaultName}-staging";
        $stagingBranch = 'staging';
        $stagingKeySecret = 'UBERSPACE_STAGING_SSH_KEY';

        if ($configureStaging) {
            $stagingUser = $this->ask('Staging SSH User', $prodUser);
            $stagingHost = $this->ask('Staging SSH Host / Server', $prodHost);
            $stagingPath = $this->ask('Staging Remote App Path', "/var/www/virtual/{$stagingUser}/{$defaultName}-staging");
            $stagingBranch = $this->ask('Staging Git Branch', 'staging');
            $stagingKeySecret = $this->ask('GitHub Secret Name for Staging SSH Key', 'UBERSPACE_STAGING_SSH_KEY');
        }

        // Common Replacements
        $replacements = [
            '{{APP_NAME}}' => $appName,
            '{{APP_DOMAIN}}' => $appDomain,
            '{{PROJECT_TYPE}}' => $projectType,
            '{{PROCESS_MANAGER}}' => $processManager,
            '{{SSR_PROCESS}}' => $ssrProcess,
            '{{QUEUE_PROCESS}}' => $queueProcess,
            '{{PROD_USER}}' => $prodUser,
            '{{PROD_HOST}}' => $prodHost,
            '{{PROD_APP_PATH}}' => $prodPath,
            '{{PROD_BRANCH}}' => $prodBranch,
            '{{PROD_SSH_KEY_SECRET}}' => $prodKeySecret,
            '{{STAGING_USER}}' => $stagingUser,
            '{{STAGING_HOST}}' => $stagingHost,
            '{{STAGING_APP_PATH}}' => $stagingPath,
            '{{STAGING_BRANCH}}' => $stagingBranch,
            '{{STAGING_SSH_KEY_SECRET}}' => $stagingKeySecret,
        ];

        // 4. Generate Files
        $this->comment("\n--- 📦 Generating Deployment Suite ---");

        // A. deploy.config.json
        $this->generateFromStub('deploy.config.json.stub', 'deploy.config.json', $replacements);

        // B. Local Orchestrator (ship.sh)
        $this->generateFromStub('ship.stub', 'ship.sh', $replacements, true);

        // C. Remote Executor (deploy.sh)
        $this->generateFromStub('deploy.stub', 'deploy.sh', $replacements, true);

        // D. Workflows Directory
        $workflowsDir = base_path('.github/workflows');
        if (! File::isDirectory($workflowsDir)) {
            File::makeDirectory($workflowsDir, 0755, true);
        }

        // E. GitHub Action: Production
        $this->generateFromStub('deploy-production.stub', '.github/workflows/deploy.yml', $replacements);

        // F. GitHub Action: Staging (If enabled)
        if ($configureStaging) {
            $this->generateFromStub('deploy-staging.stub', '.github/workflows/deploy-staging.yml', $replacements);
        }

        $this->info("\n=================================================");
        $this->info("   ✅ Deployment Suite Ready!                    ");
        $this->info("=================================================");
        $this->line("• Local Fast Ship:    ./ship.sh");
        $this->line("• Check Server Status: ./ship.sh status");
        $this->line("• GitHub Action:      .github/workflows/deploy.yml");
        $this->line("• Configuration:      deploy.config.json");
        $this->info("=================================================");
    }

    protected function generateFromStub($stubName, $destinationName, $replacements, $makeExecutable = false)
    {
        $stubPath = __DIR__ . "/../Stubs/{$stubName}";
        $destinationPath = base_path($destinationName);

        if (! File::exists($stubPath)) {
            $this->error("Missing stub: {$stubPath}");
            return;
        }

        if (File::exists($destinationPath) && ! $this->option('force')) {
            if (! $this->confirm("File '{$destinationName}' already exists. Overwrite?", true)) {
                $this->comment("Skipped: {$destinationName}");
                return;
            }
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        File::put($destinationPath, $content);

        if ($makeExecutable && function_exists('chmod')) {
            @chmod($destinationPath, 0755);
        }

        $this->info("✓ Generated: {$destinationName}");
    }
}
