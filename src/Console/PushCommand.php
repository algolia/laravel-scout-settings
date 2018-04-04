<?php

namespace Algolia\Settings\Console;

use Algolia\Settings\IndexName;
use Laravel\Scout\Searchable;

final class PushCommand extends AlgoliaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'algolia:settings:push {model} {--prefix}';

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

            return 1; // Return value >0 to indicate error. Bash "1" means "general error"
        }

        if ($usePrefix = $this->option('prefix')) {
            $this->warn('All resources will be looked up in files prefixed with '.config('scout.prefix'));
        }

        $indexName = IndexName::createFromRemote((new $fqn)->searchableAs(), $usePrefix);

        $done = $this->pushSettings($indexName);

        if ($done) {
            $this->info('All settings for ['.$fqn.'] index have been pushed.');
        }

        $done = $this->pushSynonyms($indexName);

        if ($done) {
            $this->info('All synonyms for ['.$fqn.'] index have been pushed.');
        }

        $done = $this->pushRules($indexName);

        if ($done) {
            $this->info('All query rules for ['.$fqn.'] index have been pushed.');
        }
    }

    protected function pushSettings(IndexName $indexName)
    {
        $settings = $this->indexRepository->getSettings($indexName);

        if (! $settings) {
            $this->warn('No settings to push to '.$indexName);
            return false;
        }

        $this->line('Pushing settings for ' . $indexName . ' index.');

        if (isset($settings['replicas'])) {
            foreach ($settings['replicas'] as $replica) {
                $replicaName = IndexName::createFromLocal($replica, $this->option('prefix'));
                $this->pushSettings($replicaName);
            }
        }

        $this->getIndex($indexName)->setSettings($settings);

        return true;
    }

    protected function pushSynonyms(IndexName $indexName)
    {
        $synonyms = $this->indexRepository->getSynonyms($indexName);

        if (! $synonyms) {
            $this->warn('No synonyms to push to '.$indexName);
            return false;
        }

        $index = $this->getIndex($indexName);
        // Clear all synonyms from the index
        $task = $index->clearSynonyms(true);
        $index->waitTask($task['taskID']);

        $this->line('Pushing synonyms for ' . $indexName . ' index.');

        foreach (array_chunk($synonyms, 1000) as $batch) {
            $index->batchSynonyms($batch, true, true);
        }

        return true;
    }

    protected function pushRules(IndexName $indexName)
    {
        $rules = $this->indexRepository->getRules($indexName);

        if (! $rules) {
            $this->warn('No query rules to push to '.$indexName);
            return false;
        }

        $index = $this->getIndex($indexName);
        // Clear all rules from the index
        $task = $index->clearRules(true);
        $index->waitTask($task['taskID']);

        $this->line('Pushing rules for ' . $indexName . ' index.');

        foreach (array_chunk($rules, 1000) as $batch) {
            $index->batchRules($batch, true, true);
        }

        return true;
    }
}
