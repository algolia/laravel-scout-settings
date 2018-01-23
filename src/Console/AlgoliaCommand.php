<?php

namespace Algolia\Settings\Console;

use Algolia\Settings\Services\Resource as ResourceService;
use Algolia\Settings\Services\Exceptions\NotSearchableException;
use AlgoliaSearch\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AlgoliaCommand extends Command
{
    protected $path;

    protected $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        parent::__construct();

        $this->resourceService = $resourceService;

        $path = $this->resourceService->getFilePath();
        // Ensure settings directory exists
        if (! File::exists($path)) {
            File::makeDirectory($path);
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
        try {
            $this->resourceService->validateClassSearchable($class);
        } catch (NotSearchableException $e) {
            $this->warn($e->getMessage());
            return false;
        }

        return true;
    }
}
