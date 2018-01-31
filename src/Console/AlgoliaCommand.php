<?php

namespace Algolia\Settings\Console;

use Algolia\Settings\IndexRepository;
use AlgoliaSearch\Client;
use Illuminate\Console\Command;

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
        return app(Client::class)->initIndex($indexName);
    }

    protected function isClassSearchable($class)
    {
        return $this->indexRepository->validateClassSearchable($class);
    }
}
