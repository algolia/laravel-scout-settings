<?php

namespace Algolia\Settings;

use Algolia\Settings\Console\BackupCommand;
use AlgoliaSearch\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Algolia\Settings\Console\PushCommand;

final class ServiceProvider extends LaravelServiceProvider
{
    const VERSION = '1.0.0';

    public function register()
    {
        $this->commands([
            PushCommand::class,
            BackupCommand::class,
        ]);

        // Service class is stateless so instantiate once
        $this->app->singleton(IndexRepository::class, IndexRepository::class);

        $this->app->bind(Client::class, function (Application $application) {
            return new Client(
                $application->make('config')->get('scout.algolia.id'),
                $application->make('config')->get('scout.algolia.secret')
            );
        });
    }
}
