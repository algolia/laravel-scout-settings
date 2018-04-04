<?php

namespace Algolia\Settings\Console;

use Algolia\Settings\IndexName;
use Algolia\Settings\IndexResourceRepository;
use AlgoliaSearch\Client;
use Illuminate\Console\Command;
use Laravel\Scout\Searchable;

abstract class AlgoliaCommand extends Command
{
    protected $indexRepository;

    public function __construct(IndexResourceRepository $indexRepository)
    {
        parent::__construct();

        $this->indexRepository = $indexRepository;
    }

    protected function getIndex(IndexName $indexName)
    {
        return app(Client::class)->initIndex($indexName->remote());
    }

    protected function isClassSearchable($fqn)
    {
        if (! \in_array(Searchable::class, class_uses_recursive($fqn), true)) {
            return false;
        }
        return true;
    }
}
