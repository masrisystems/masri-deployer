<?php

namespace Masri\Deployer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckDeployment extends Command
{
    protected $signature = 'deploy:check';

    protected $description = 'Verify local requirements and remote SSH connectivity for deployment';

    public function handle()
    {
        $this->info("=================================================");
        $this->info("   🔍 Masri Deployer — Pre-Flight Health Check   ");
        $this->info("=================================================");

        $configPath = base_path('deploy.config.json');
        if (! File::exists($configPath)) {
            $this->error("❌ Configuration file 'deploy.config.json' not found. Run 'php artisan deploy:init' first.");
            return 1;
        }

        $config = json_decode(File::get($configPath), true);
        $prod = $config['environments']['production'] ?? [];
        $user = $prod['user'] ?? '';
        $host = $prod['host'] ?? '';
        $path = $prod['app_path'] ?? '';

        // 1. Check local tools
        $this->comment("Local Prerequisites:");
        $tools = [
            'git' => 'git --version',
            'composer' => 'composer --version',
            'npm' => 'npm --version',
            'ssh' => 'ssh -V 2>&1',
        ];

        foreach ($tools as $tool => $cmd) {
            $output = [];
            $code = 0;
            @exec($cmd, $output, $code);
            if ($code === 0) {
                $this->line("  ✓ {$tool}: " . ($output[0] ?? 'Available'));
            } else {
                $this->warn("  ⚠️ {$tool}: Not found or returned error");
            }
        }

        // 2. Check SSH Connection
        $this->comment("\nRemote Server Connectivity ({$user}@{$host}):");
        $sshTestCmd = "ssh -o ConnectTimeout=6 -o BatchMode=yes {$user}@{$host} 'echo OK' 2>&1";
        $sshOutput = [];
        $sshCode = 0;
        @exec($sshTestCmd, $sshOutput, $sshCode);

        if ($sshCode === 0 && in_array('OK', $sshOutput)) {
            $this->info("  ✓ SSH Connection Successful!");

            // 3. Check Remote App Directory
            $dirCheckCmd = "ssh {$user}@{$host} '[ -d \"{$path}\" ] && echo EXISTS || echo MISSING'";
            $dirOutput = [];
            @exec($dirCheckCmd, $dirOutput);
            if (in_array('EXISTS', $dirOutput)) {
                $this->info("  ✓ Remote Directory Exists: {$path}");
            } else {
                $this->warn("  ⚠️ Remote Directory Missing or Not Created Yet: {$path}");
            }
        } else {
            $this->error("  ❌ SSH Connection Failed!");
            $this->line("     Details: " . implode(' ', $sshOutput));
        }

        return 0;
    }
}
