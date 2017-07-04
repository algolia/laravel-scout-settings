<?php

namespace Algolia\Settings\Console;

use AlgoliaSearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class AlgoliaCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        // Ensure settings directory exists
            if (! File::exists(resource_path('settings/'))) {
            File::makeDirectory();
        }
    }

    protected function getIndex($indexName)
    {
        return (new Client(
            config('scout.algolia.id'),
            config('scout.algolia.secret'))
        )->initIndex($indexName);
    }
}
