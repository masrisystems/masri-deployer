<?php

namespace Masri\Deployer;

use Illuminate\Support\ServiceProvider;
use Masri\Deployer\Commands\InitDeployment;
use Masri\Deployer\Commands\CheckDeployment;
use Masri\Deployer\Commands\StatusDeployment;

class DeployerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InitDeployment::class,
                CheckDeployment::class,
                StatusDeployment::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
