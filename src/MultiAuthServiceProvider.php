<?php

namespace Chweb\MultiAuth;

use Illuminate\Support\ServiceProvider;
use Chweb\MultiAuth\Console\InstallCommand;

class MultiAuthServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.1';

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        //
    }
}
