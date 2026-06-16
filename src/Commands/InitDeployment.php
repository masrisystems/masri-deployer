<?php

namespace Masri\Deployer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitDeployment extends Command
{
    protected $signature = 'deploy:init';
    protected $description = 'Generate GitHub Actions deployment workflows (deploy-staging.yml and deploy.yml)';

    public function handle()
    {
        $this->info("🚀 Initializing Masri GitHub Actions Deployment Workflows...");

        // 1. App name & SSR process
        $defaultApp = basename(base_path());
        $appName = $this->ask('VITE App Name (VITE_APP_NAME)', config('app.name') ?: ucfirst($defaultApp));
        $ssrProcess = $this->ask('PM2 / SSR Process Name', "{$defaultApp}-ssr");

        // 2. Staging Configuration
        $this->comment("\n--- Staging Environment Settings ---");
        $stagingUser = $this->ask('Staging SSH User', 'ranalp');
        $stagingHost = $this->ask('Staging Host', 'kallistro.uberspace.de');
        $stagingPath = $this->ask('Staging Remote App Path', "/var/www/virtual/{$stagingUser}/{$defaultApp}");
        $stagingKey = $this->ask('Staging SSH Key GitHub Secret Name', 'UBERSPACE_STAGING_SSH_KEY');
        $stagingBranch = $this->ask('Staging Git Branch', 'staging');

        // 3. Production Configuration
        $this->comment("\n--- Production Environment Settings ---");
        $prodUser = $this->ask('Production SSH User', 'masri');
        $prodHost = $this->ask('Production Host', 'gienah.uberspace.de');
        $prodPath = $this->ask('Production Remote App Path', "/var/www/virtual/{$prodUser}/{$defaultApp}");
        $prodKey = $this->ask('Production SSH Key GitHub Secret Name', 'UBERSPACE_SSH_KEY');
        $prodBranch = $this->ask('Production Git Branch', 'main');

        // 4. Ensure Workflows Directory exists
        $workflowsDir = base_path('.github/workflows');
        if (!File::isDirectory($workflowsDir)) {
            File::makeDirectory($workflowsDir, 0755, true);
        }

        // 5. Generate Workflow Files
        $this->generateFile('deploy-staging.stub', '.github/workflows/deploy-staging.yml', [
            '{{STAGING_BRANCH}}' => $stagingBranch,
            '{{VITE_APP_NAME}}' => $appName,
            '{{STAGING_SSH_KEY_SECRET}}' => $stagingKey,
            '{{STAGING_USER}}' => $stagingUser,
            '{{STAGING_HOST}}' => $stagingHost,
            '{{STAGING_APP_PATH}}' => $stagingPath,
            '{{SSR_PROCESS}}' => $ssrProcess,
        ]);

        $this->generateFile('deploy-production.stub', '.github/workflows/deploy.yml', [
            '{{PROD_BRANCH}}' => $prodBranch,
            '{{VITE_APP_NAME}}' => $appName,
            '{{PROD_SSH_KEY_SECRET}}' => $prodKey,
            '{{PROD_USER}}' => $prodUser,
            '{{PROD_HOST}}' => $prodHost,
            '{{PROD_APP_PATH}}' => $prodPath,
            '{{SSR_PROCESS}}' => $ssrProcess,
        ]);

        // 6. Clean up old .sh scripts if they exist
        $this->comment("\n--- Cleaning up legacy shell deployment scripts ---");
        foreach (['ship.sh', 'deploy.sh', 'deploy_backup.sh'] as $shFile) {
            $shPath = base_path($shFile);
            if (File::exists($shPath)) {
                if ($this->confirm("Found legacy file '{$shFile}'. Do you want to delete it?", true)) {
                    File::delete($shPath);
                    $this->info("Deleted legacy file: {$shFile}");
                }
            }
        }

        $this->info("\n✅ GitHub Actions workflows generated successfully!");
        $this->info("👉 Push to the '{$stagingBranch}' or '{$prodBranch}' branch to deploy.");
    }

    protected function generateFile($stubName, $destinationName, $replacements)
    {
        $stubPath = __DIR__ . "/../Stubs/$stubName";
        $destinationPath = base_path($destinationName);

        if (File::exists($destinationPath)) {
            if (!$this->confirm("File $destinationName already exists. Overwrite?")) {
                return;
            }
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        File::put($destinationPath, $content);
        $this->line("Created: $destinationName");
    }
}