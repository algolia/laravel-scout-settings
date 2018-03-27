<?php

namespace Algolia\Settings\Console;

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

    protected function getIndex($indexName)
    {
        return app(Client::class)->initIndex($indexName);
    }

    protected function isClassSearchable($fqn)
    {
        if (! \in_array(Searchable::class, class_uses_recursive($fqn), true)) {
            return false;
        }
        return true;
    }

    protected function canonicalIndexName($indexName)
    {
        if ($this->option('prefix')) {
            return $indexName;
        }

        return $this->removePrefix($indexName);
    }

    private function removePrefix($indexName)
    {
        return preg_replace('/^'.preg_quote(config('scout.prefix'), '/').'/', '', $indexName);
    }
}
