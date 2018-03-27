<?php

namespace Algolia\Settings;

use Algolia\Settings\Console\BackupCommand;
use Exception;
use AlgoliaSearch\Client;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Algolia\Settings\Console\PushCommand;
use AlgoliaSearch\Version as AlgoliaUserAgent;

final class ServiceProvider extends LaravelServiceProvider
{
    const VERSION = '2.0.1';

    public function boot()
    {
        AlgoliaUserAgent::addSuffixUserAgentSegment('; Laravel Scout settings package', self::VERSION);

        if (!class_exists('AlgoliaSearch\Client')) {
            throw new Exception("It seems like you are not using Algolia. This package requires the Algolia engine.", 1);
        }
    }

    public function register()
    {
        $this->commands([
            PushCommand::class,
            BackupCommand::class,
        ]);

        // Service class is stateless so instantiate once
        $this->app->singleton(IndexResourceRepository::class, IndexResourceRepository::class);

        $this->app->bind(Client::class, function (Application $application) {
            return new Client(
                $application->make('config')->get('scout.algolia.id'),
                $application->make('config')->get('scout.algolia.secret')
            );
        });
    }
}
