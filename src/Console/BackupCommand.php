<?php

namespace Algolia\Settings\Console;

use Illuminate\Support\Facades\File;

class BackupCommand extends AlgoliaCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'algolia:settings:backup {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Back up all settings of the given model into the a json file in your project';

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

        $success = $this->saveSettings($indexName);

        if ($success) {
            $this->info('All settings for ['.$class.'] index have been backed up.');
        } else {
            $this->warn('The settings could not be saved');
        }

        $success = $this->saveSynonyms($indexName);

        if ($success) {
            $this->info('All synonyms for ['.$class.'] index have been backed up.');
        } else {
            $this->warn('The synonyms could not be saved');
        }

        $success = $this->saveRules($indexName);

        if ($success) {
            $this->info('All query rules for ['.$class.'] index have been backed up.');
        } else {
            $this->warn('The query rules could not be saved');
        }
    }

    protected function saveSettings($indexName)
    {
        $filename = $this->path.$indexName.'.json';
        $settings = $this->getIndex($indexName)->getSettings();

        $child = true;
        if (isset($settings['replicas'])) {
            foreach ($settings['replicas'] as $replica) {
                $child = $this->saveSettings($replica);
            }
        }

        $success = File::put(
            $filename,
            json_encode($settings, JSON_PRETTY_PRINT)
        );

        if ($success) {
            $this->line($filename.' was created.');
        }

        return $success && $child;
    }

    protected function saveSynonyms($indexName)
    {
        $filename = $this->path.$indexName.'-synonyms.json';
        $synonymIterator = $this->getIndex($indexName)->initSynonymIterator();
        $synonyms = [];

        foreach ($synonymIterator as $synonym) {
            $synonyms[] = $synonym;
        }

        $success = File::put(
            $filename,
            json_encode($synonyms, JSON_PRETTY_PRINT)
        );

        if ($success) {
            $this->line($filename.' was created.');
        }

        return $success;
    }

    protected function saveRules($indexName)
    {
        $filename = $this->path.$indexName.'-rules.json';
        $ruleIterator = $this->getIndex($indexName)->initRuleIterator();
        $rules = [];

        foreach ($ruleIterator as $rule) {
            $rules[] = $rule;
        }

        $success = File::put(
            $filename,
            json_encode($rules, JSON_PRETTY_PRINT)
        );

        if ($success) {
            $this->line($filename.' was created.');
        }

        return $success;
    }
}
