<?php

namespace Algolia\Settings\Tests\Algolia\Settings\Services;

use Algolia\Settings\Services\Exceptions\NotSearchableException;
use Algolia\Settings\Services\Resource;
use Laravel\Scout\Searchable;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;

final class ResourceTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $file_system;

    public function setUp()
    {
        parent::setUp();

        // define my virtual file system
        $structure = [
            'resources' => [
                'algolia-settings' => [
                    'posts.json' => <<<'POST'
{
    "searchableAttributes": [
        "title"
    ],
    "replicas": [
        "posts_newest",
        "posts_oldest"
    ],
    "customRanking": null
}
POST
                    ,
                    'posts_newest' => <<<'POST'
{
    "searchableAttributes": [
        "title"
    ],
    "primary": "posts",
    "customRanking": [
        "desc(published_at)"
    ]
}
POST
                    ,
                    'posts_oldest' => <<<'POST'
{
    "searchableAttributes": [
        "title"
    ],
    "primary": "posts",
    "customRanking": [
        "asc(published_at)"
    ]
}
POST
                    ,
                ],
                'custom-sub-path-settings' => [
                    'posts.json' => <<<'POST'
{
    "searchableAttributes": [
        "title",
        "summary"
    ]
}
POST
                    ,
                ],
            ],
        ];

        // setup and cache the virtual file system
        $this->file_system = vfsStream::setup('root', null, $structure);

        $this->app->setBasePath($this->file_system->url());
    }

    public function testGetSettingsDefaultLocation()
    {
        $sut = new Resource();

        $expected = [
            'title'
        ];

        $postSettings = $sut->getSettings(FakeModelWithSearchableTrait::class);
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);
    }

    public function testGetSettingsCustomLocation()
    {
        $org_env = env('ALGOLIA_SETTINGS_FOLDER');
        if($org_env === null) {
            // Currently not set. `putenv('SOME_KEY')`, so without equals sign,
            // will actually unset the env variable.
            $org_env = 'ALGOLIA_SETTINGS_FOLDER';
        } else {
            $org_env = 'ALGOLIA_SETTINGS_FOLDER=' . $org_env;
        }
        putenv('ALGOLIA_SETTINGS_FOLDER=custom-sub-path-settings/');

        $sut = new Resource();

        $expected = [
            'title',
            'summary',
        ];

        $postSettings = $sut->getSettings(FakeModelWithSearchableTrait::class);
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);

        putenv($org_env);
    }

    public function testValidateClassSearchableSuccessful()
    {
        $sut = new Resource();

        $this->assertTrue($sut->validateClassSearchable(FakeModelWithSearchableTrait::class));
    }

    public function testValidateClassSearchableTraitNotImplemented()
    {
        $this->expectException(NotSearchableException::class);

        $sut = new Resource();

        $sut->validateClassSearchable(FakeModelWithoutSearchableTrait::class);
    }

    public function testClassToIndexName()
    {
        $sut = new Resource();

        $expected = 'posts';

        $actual = $sut->classToIndexName(FakeModelWithSearchableTrait::class);

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePath()
    {
        $sut = new Resource();

        $expected = 'vfs://root/resources/algolia-settings/';

        $actual = $sut->getFilePath();

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePathSubPath()
    {
        $sut = new Resource();

        $expected = 'vfs://root/resources/algolia-settings/foo/';

        $actual = $sut->getFilePath('foo/');

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePathFile()
    {
        $sut = new Resource();

        $expected = 'vfs://root/resources/algolia-settings/bar.json';

        $actual = $sut->getFilePath('bar.json');

        $this->assertEquals($expected, $actual);
    }
}

/**
 * Tmp class. Since Laravel 5.4 is supported, the minimum version of PHP is
 * 5.6.4, anonymous class would be better here, but only available since PHP 7.
 */
class FakeModelWithSearchableTrait
{
    use Searchable;

    public function getTable()
    {
        return 'posts';
    }
}
class FakeModelWithoutSearchableTrait
{
    public function getTable()
    {
        return 'posts';
    }
}
