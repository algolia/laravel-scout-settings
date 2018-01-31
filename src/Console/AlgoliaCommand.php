<?php

namespace Algolia\Settings\Console;

use Algolia\Settings\IndexRepository;
use Algolia\Settings\ServiceProvider;
use AlgoliaSearch\Client;
use Illuminate\Console\Command;
use AlgoliaSearch\Version as AlgoliaUserAgent;
use Illuminate\Support\Facades\File;

abstract class AlgoliaCommand extends Command
{
    protected $indexRepository;

    public function __construct(IndexRepository $indexRepository)
    {
        parent::__construct();

        $this->indexRepository = $indexRepository;
    }

    protected function getIndex($indexName)
    {
        AlgoliaUserAgent::addSuffixUserAgentSegment('; Laravel Scout settings package', ServiceProvider::VERSION);

        return app(Client::class)->initIndex($indexName);
    }

    protected function isClassSearchable($class)
    {
        return $this->indexRepository->validateClassSearchable($class);
    }
}
