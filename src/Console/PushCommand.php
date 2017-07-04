<?php

namespace Algolia\Settings\Console;

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

        $indexName = (new $class)->searchableAs();

        $index = $this->getIndex($indexName);

        $settings = json_decode(File::get(
            resource_path('settings/'.$indexName.'json')
        ));

        $index->setSettings($settings);

        $this->info('All settings for ['.$class.'] index have been pushed.');
    }
}
