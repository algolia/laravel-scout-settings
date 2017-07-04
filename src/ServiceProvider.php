<?php

namespace Algolia\Settings;

use Algolia\Settings\Console\BackupCommand;
use Illuminate\Support\Facades\File;
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
    }
}
