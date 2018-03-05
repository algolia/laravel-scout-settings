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
        $settings = $this->getIndex($indexName)->getSettings();

        $child = true;
        if (isset($settings['replicas'])) {
            foreach ($settings['replicas'] as $replica) {
                $child = $this->saveSettings($replica);
            }
        }

        $success = $this->indexRepository->saveSettings($indexName, $settings);

        if ($success) {
            $this->line("Saved settings for $indexName");
        }

        return $success && $child;
    }

    protected function saveSynonyms($indexName)
    {
        $synonymIterator = $this->getIndex($indexName)->initSynonymIterator();

        $synonyms = [];
        foreach ($synonymIterator as $synonym) {
            $synonyms[] = $synonym;
        }

        $success = $this->indexRepository->saveSynonyms($indexName, $synonyms);

        if ($success) {
            $this->line("Saved synonyms for $indexName");
        }

        return $success;
    }

    protected function saveRules($indexName)
    {
        $ruleIterator = $this->getIndex($indexName)->initRuleIterator();

        $rules = [];
        foreach ($ruleIterator as $rule) {
            $rules[] = $rule;
        }

        $success = $this->indexRepository->saveRules($indexName, $rules);

        if ($success) {
            $this->line("Saved synonyms for $indexName");

        }

        return $success;
    }
}
