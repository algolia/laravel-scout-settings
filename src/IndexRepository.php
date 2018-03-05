<?php

namespace Algolia\Settings;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class IndexRepository
{
    public function __construct()
    {
        if (! File::exists($path = $this->getBasePath())) {
            File::makeDirectory($path);
        }
    }

    public function getSettings($indexName)
    {
        return Json::decode(
            File::get($this->getFilePath($indexName, 'settings')),
            true
        );
    }

    public function saveSettings($indexName, $settings)
    {
        return File::put(
            $this->getFilePath($indexName, 'settings'),
            Json::encode($settings, JSON_PRETTY_PRINT)
        );
    }

    public function getSynonyms($indexName)
    {
        return Json::decode(
            File::get($this->getFilePath($indexName, 'synonyms')),
            true
        );
    }

    public function saveSynonyms($indexName, $synonyms)
    {
        return File::put(
            $this->getFilePath($indexName, 'synonyms'),
            Json::encode($synonyms, JSON_PRETTY_PRINT)
        );
    }

    public function getRules($indexName)
    {
        return Json::decode(
            File::get($this->getFilePath($indexName, 'rules')),
            true
        );
    }

    public function saveRules($indexName, $rules)
    {
        return File::put(
            $this->getFilePath($indexName, 'rules'),
            Json::encode($rules, JSON_PRETTY_PRINT)
        );
    }

    private function getFilePath($indexName, $type)
    {
        $path = $this->getBasePath() . $indexName . $this->normalizeType($type) . '.json';

        return $path;
    }

    private function normalizeType($type)
    {
        if ('settings' === $type) {
            return '';
        }

        return '-' . $type;
    }

    private function getBasePath()
    {
        return rtrim(
                app()->resourcePath(
                    env('ALGOLIA_SETTINGS_FOLDER', 'algolia-settings/')
                ),
                '/'
            ) . DIRECTORY_SEPARATOR;
    }
}
