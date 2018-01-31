<?php

namespace Algolia\Settings\Console;

use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class BackupCommand extends AlgoliaCommand
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

        $success = $this->saveSettings($indexName);

        if ($success) {
            $this->info('All settings for ['.$fqn.'] index have been backed up.');
        } else {
            $this->warn('The settings could not be saved');
        }

        $success = $this->saveSynonyms($indexName);

        if ($success) {
            $this->info('All synonyms for ['.$fqn.'] index have been backed up.');
        } else {
            $this->warn('The synonyms could not be saved');
        }

        $success = $this->saveRules($indexName);

        if ($success) {
            $this->info('All query rules for ['.$fqn.'] index have been backed up.');
        } else {
            $this->warn('The query rules could not be saved');
        }
    }

    protected function saveSettings($indexName)
    {
        $filename = $this->indexRepository->getFilePath($indexName);
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
        $filename = $this->indexRepository->getFilePath($indexName, 'synonyms');
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
        $filename = $this->indexRepository->getFilePath($indexName, 'rules');
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
