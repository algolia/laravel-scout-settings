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

        $index = $this->getIndex($indexName);

        $jsonSettings = File::get($this->path.$indexName.'.json');

        if (! $jsonSettings) {
            $this->warn("It seems that you don't have any settings to push for [$class].");
        }

        $index->setSettings(Json::decode($jsonSettings));

        $this->info('All settings for ['.$class.'] index have been pushed.');
    }
}
