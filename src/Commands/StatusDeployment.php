<?php

namespace Masri\Deployer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StatusDeployment extends Command
{
    protected $signature = 'deploy:status';

    protected $description = 'Inspect remote server deployment status, processes, and maintenance state';

    public function handle()
    {
        $configPath = base_path('deploy.config.json');
        if (! File::exists($configPath)) {
            $this->error("❌ 'deploy.config.json' not found. Run 'php artisan deploy:init' first.");
            return 1;
        }

        $config = json_decode(File::get($configPath), true);
        $prod = $config['environments']['production'] ?? [];
        $user = $prod['user'] ?? '';
        $host = $prod['host'] ?? '';
        $path = $prod['app_path'] ?? '';
        $ssr = $prod['ssr_process'] ?? '';

        $this->info("=================================================");
        $this->info("   📊 Remote Server Status: {$host} ({$user})    ");
        $this->info("=================================================");

        $remoteCmd = "ssh {$user}@{$host} '
            export PATH=\$HOME/bin:\$PATH
            echo \"=== Process Manager ===\"
            if command -v supervisorctl &> /dev/null; then
                supervisorctl status 2>/dev/null || true
            fi
            if command -v systemctl &> /dev/null && [ -n \"{$ssr}\" ]; then
                systemctl --user status {$ssr} --no-pager 2>/dev/null || true
            fi
            if command -v pm2 &> /dev/null; then
                pm2 list 2>/dev/null || true
            fi

            echo \"\"
            echo \"=== Maintenance Mode ===\"
            if [ -f \"{$path}/storage/framework/down\" ]; then
                echo \"❌ Application is in MAINTENANCE MODE (Down)\"
            else
                echo \"✅ Application is LIVE (Up)\"
            fi

            echo \"\"
            echo \"=== Build Asset Footprint ===\"
            du -sh {$path}/public/build 2>/dev/null || echo \"No build directory found\"
        '";

        passthru($remoteCmd);
        return 0;
    }
}
