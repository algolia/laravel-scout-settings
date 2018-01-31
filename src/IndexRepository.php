<?php

namespace Algolia\Settings;

use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class IndexRepository
{
    public function __construct()
    {
        // Ensure settings directory exists
        if (! File::exists($path = $this->getBasePath())) {
            File::makeDirectory($path);
        }
    }

    public function getSettings($indexName)
    {
        return Json::decode(
            File::get($this->getFilePath($indexName)),
            true
        );
    }

    /**
     * @param string $fqn
     *
     * @return bool
     */
    public function validateClassSearchable($fqn)
    {
        if (! in_array(Searchable::class, class_uses_recursive($fqn))) {
            return false;
        }

        return true;
    }

    public function getFilePath($indexName, $type = 'settings')
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
