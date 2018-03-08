<?php

namespace Algolia\Settings\Console;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class PushCommand extends AlgoliaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'algolia:settings:push {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push all settings of the given model into the Algolia index';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fqn = $this->argument('model');

        if (! $this->isClassSearchable($fqn)) {
            $this->warn('The class [' . $fqn . '] does not use the [' . Searchable::class . '] trait');

            // Return value >0 to indicate error. Bash "1" means "general error"
            return 1;
        }

        $indexName = (new $fqn)->searchableAs();

        $this->pushSettings($indexName);

        $this->info('All settings for ['.$fqn.'] index have been pushed.');

        $this->pushSynonyms($indexName);

        $this->info('All synonyms for ['.$fqn.'] index have been pushed.');

        $this->pushRules($indexName);

        $this->info('All query rules for ['.$fqn.'] index have been pushed.');
    }

    protected function pushSettings($indexName)
    {
        $settings = $this->indexRepository->getSettings($indexName);

        if (isset($settings['replicas'])) {
            foreach ($settings['replicas'] as $replica) {
                $this->pushSettings($replica);
            }
        }

        $this->getIndex($indexName)->setSettings($settings);

        $this->line('Pushing settings for ' . $indexName . ' index.');
    }

    protected function pushSynonyms($indexName)
    {
        $index = $this->getIndex($indexName);

        // Clear all synonyms from the index
        $task = $index->clearSynonyms(true);
        $index->waitTask($task['taskID']);

        $synonyms = $this->indexRepository->getSynonyms($indexName);

        foreach (array_chunk($synonyms, 1000) as $batch) {
            $index->batchSynonyms($batch, true, true);
        }

        $this->line('Pushing synonyms for ' . $indexName . ' index.');
    }

    protected function pushRules($indexName)
    {
        $index = $this->getIndex($indexName);

        // Clear all rules from the index
        $task = $index->clearRules(true);
        $index->waitTask($task['taskID']);

        $rules = $this->indexRepository->getRules($indexName);

        foreach (array_chunk($rules, 1000) as $batch) {
            $index->batchRules($batch, true, true);
        }

        $this->line('Pushing rules for ' . $indexName . ' index.');
    }
}
