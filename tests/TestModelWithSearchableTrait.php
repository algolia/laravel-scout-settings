<?php

namespace Ubeeqo\Algolia\Settings\Tests;

use Laravel\Scout\Searchable;

/**
 * Tmp class. Since Laravel 5.4 is supported, the minimum version of PHP is
 * 5.6.4, anonymous class would be better here, but only available since PHP 7.
 */
final class TestModelWithSearchableTrait
{
    use Searchable;

    public function getTable()
    {
        return 'posts';
    }
}
