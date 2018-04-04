<?php

namespace Algolia\Settings;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;

final class IndexResourceRepository
{
    public function __construct()
    {
        if (! File::exists($path = $this->getBasePath())) {
            File::makeDirectory($path);
        }
    }

    public function getSettings(IndexName $indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'settings'));
    }

    public function saveSettings(IndexName $indexName, $settings)
    {
        return $this->saveJsonFile(
            $this->getFilePath($indexName, 'settings'),
            $this->normalizeSettings($indexName, $settings)
        );
    }

    public function getSynonyms(IndexName $indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'synonyms'));
    }

    public function saveSynonyms(IndexName $indexName, $synonyms)
    {
        return $this->saveJsonFile($this->getFilePath($indexName, 'synonyms'), $synonyms);
    }

    public function getRules(IndexName $indexName)
    {
        return $this->getJsonFromFile($this->getFilePath($indexName, 'rules'));
    }

    public function saveRules(IndexName $indexName, $rules)
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

    private function getFilePath(IndexName $indexName, $type)
    {
        $path = $this->getBasePath() . $indexName->local() . $this->normalizeType($type) . '.json';

        return $path;
    }

    private function normalizeSettings(IndexName $indexName, array $json)
    {
        if($indexName->usesPrefix()) {
            // Indices in JSON should contain the prefix
            return $json;
        }

        if (isset($json['primary'])) {
            $json['primary'] = preg_replace(
                '/^' . preg_quote(config('scout.prefix'), '/') . '/',
                '',
                $json['primary']
            );
        }

        if(isset($json['replicas'])) {
            foreach($json['replicas'] as &$replica) {
                $replica = preg_replace(
                    '/^' . preg_quote(config('scout.prefix'), '/') . '/',
                    '',
                    $replica
                );
            }
        }

        return $json;
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
