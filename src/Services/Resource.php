<?php

namespace Algolia\Settings\Services;

use Algolia\Settings\Services\Exceptions\NotSearchableException;
use AlgoliaSearch\Json;
use Illuminate\Support\Facades\File;
use Laravel\Scout\Searchable;

final class Resource
{
    public function getSettings($fqn)
    {
        return Json::decode(
            File::get($this->getFilePath($this->classToIndexName($fqn) . '.json')),
            true
        );
    }

    /**
     * @param $fqn
     *
     * @return bool
     * @throws NotSearchableException
     */
    public function validateClassSearchable($fqn)
    {
        if (! in_array(Searchable::class, class_uses_recursive($fqn))) {
            throw new NotSearchableException(
                'The class [' . $fqn . '] does not use the [' . Searchable::class . '] trait'
            );
        }

        return true;
    }

    public function classToIndexName($fqn)
    {
        return (new $fqn)->searchableAs();
    }

    public function getFilePath($fileName = '')
    {
        return $this->getBasePath() . $fileName;
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
