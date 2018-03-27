<?php

namespace Algolia\Settings;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class IndexResourceRepository
{
    public function __construct()
    {
        if (! File::exists($path = $this->getBasePath())) {
            File::makeDirectory($path);
        }
    }

    public function getSettings($indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'settings'));
    }

    public function saveSettings($indexName, $settings)
    {
        return $this->saveJsonFile($this->getFilePath($indexName, 'settings'), $settings);
    }

    public function getSynonyms($indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'synonyms'));
    }

    public function saveSynonyms($indexName, $synonyms)
    {
        return $this->saveJsonFile($this->getFilePath($indexName, 'synonyms'), $synonyms);
    }

    public function getRules($indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'rules'));
    }

    public function saveRules($indexName, $rules)
    {
        return $this->saveJsonFile($this->getFilePath($indexName, 'rules'), $rules);
    }

    private function getJsonFromFile($path)
    {
        if (! File::exists($path)) {
            return [];
        }

        return Json::decode(
            File::get($path),
            true
        );
    }

    private function saveJsonFile($path, $json)
    {
        return File::put(
            $path,
            Json::encode($json, JSON_PRETTY_PRINT)
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
