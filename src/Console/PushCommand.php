<?php

namespace Algolia\Settings\Console;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;

class PushCommand extends AlgoliaCommand
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
     * @return void
     */
    public function handle()
    {
        $class = $this->argument('model');

        if (! $this->isClassSearchable($class)) {
            return;
        }

        $indexName = $this->resourceService->classToIndexName($class);

        $this->pushSettings($indexName);

        $this->info('All settings for ['.$class.'] index have been pushed.');

        $this->pushSynonyms($indexName);

        $this->info('All synonyms for ['.$class.'] index have been pushed.');

        $this->pushRules($indexName);

        $this->info('All query rules for ['.$class.'] index have been pushed.');
    }

    protected function pushSettings($indexName)
    {
        $index = $this->getIndex($indexName);

        $settings = Json::decode(File::get($this->resourceService->getFilePath($indexName.'.json')), true);

        if (isset($settings['replicas'])) {
            foreach ($settings['replicas'] as $replica) {
                $this->pushSettings($replica);
            }
        }

        $index->setSettings($settings);

        $this->line('Pushing settings for '.$indexName.' index.');
    }

    protected function pushSynonyms($indexName)
    {
        $index = $this->getIndex($indexName);

        // Clear all synonyms from the index
        $task = $index->clearSynonyms(true);
        $index->waitTask($task['taskID']);

        $synonyms = Json::decode(File::get($this->resourceService->getFilePath($indexName.'-synonyms.json')), true);

        foreach (array_chunk($synonyms, 1000) as $batch) {
            $index->batchSynonyms($batch, true, true);
        }

        $this->line('Pushing synonyms for '.$indexName.' index.');
    }

    protected function pushRules($indexName)
    {
        $index = $this->getIndex($indexName);

        // Clear all rules from the index
        $task = $index->clearRules(true);
        $index->waitTask($task['taskID']);

        $rules = Json::decode(File::get($this->resourceService->getFilePath($indexName.'-rules.json')), true);

        foreach (array_chunk($rules, 1000) as $batch) {
            $index->batchRules($batch, true, true);
        }

        $this->line('Pushing rules for '.$indexName.' index.');
    }
}
