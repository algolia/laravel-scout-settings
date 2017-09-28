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

        $indexName = (new $class)->searchableAs();

        $this->pushSettings($indexName);

        $this->info('All settings for ['.$class.'] index have been pushed.');

        $this->pushSynonyms($indexName);

        $this->info('All synonyms for ['.$class.'] index have been pushed.');
    }

    protected function pushSettings($indexName)
    {
        $index = $this->getIndex($indexName);

        $settings = Json::decode(File::get($this->path.$indexName.'.json'), true);

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

        $found = Json::decode(File::get($this->path.$indexName.'-synonyms.json'), true);

        if (count($found) > 0) {
            $synonyms = [];

            foreach ($found as $synonym) {
                $synonyms[] = [
                    'objectID' => $synonym['objectID'],
                    'synonyms' => $synonym['synonyms'],
                    'type'     => $synonym['type'],
                ];
            }

            $index->batchSynonyms($synonyms, true, true);
        }

        $this->line('Pushing synonyms for '.$indexName.' index.');
    }
}
