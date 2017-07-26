<?php

namespace Algolia\Settings\Console;

use AlgoliaSearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;


class AlgoliaCommand extends Command
{
    protected $path;

    public function __construct()
    {
        parent::__construct();

        $this->path = resource_path(env('ALGOLIA_SETTINGS_FOLDER', 'algolia-settings/'));

        // Ensure settings directory exists
        if (! File::exists($this->path)) {
            File::makeDirectory($this->path);
        }
    }

    protected function getIndex($indexName)
    {
        return (new Client(
            config('scout.algolia.id'),
            config('scout.algolia.secret'))
        )->initIndex($indexName);
    }

    protected function isClassSearchable($class)
    {
        if (!in_array(Searchable::class, class_uses($class))) {
            $this->warn('The class [' . $class . '] does not use the [' . Searchable::class . '] trait');
            return false;
        }

        return true;
    }
}
