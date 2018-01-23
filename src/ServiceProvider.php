<?php

namespace Algolia\Settings;

use Algolia\Settings\Console\BackupCommand;
use Algolia\Settings\Services\Resource;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Algolia\Settings\Console\PushCommand;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->commands([
            PushCommand::class,
            BackupCommand::class,
        ]);

        // Service class is stateless so instantiate once
        $this->app->singleton(Resource::class, Resource::class);
    }
}
