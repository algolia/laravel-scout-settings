<?php

namespace Algolia\Settings;

use Algolia\Settings\Console\BackupCommand;
use Exception;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Algolia\Settings\Console\PushCommand;
use AlgoliaSearch\Version as AlgoliaUserAgent;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        AlgoliaUserAgent::addSuffixUserAgentSegment('; Laravel Scout settings package', '1.0.1');

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
    }
}
