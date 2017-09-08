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
}
