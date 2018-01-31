<?php

namespace Algolia\Settings\Tests\Algolia\Settings;

use Algolia\Settings\IndexRepository;
use Algolia\Settings\Tests\TestModel;
use Algolia\Settings\Tests\TestModelWithSearchableTrait;
use Laravel\Scout\Searchable;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;

final class IndexRepositoryTest extends TestCase
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
        $sut = new IndexRepository();

        $expected = [
            'title'
        ];

        $postSettings = $sut->getSettings((new TestModelWithSearchableTrait)->searchableAs());
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);
    }

    public function testGetSettingsCustomLocation()
    {
        $org_env = env('ALGOLIA_SETTINGS_FOLDER');
        if ($org_env === null) {
            // Currently not set. `putenv('SOME_KEY')`, so without equals sign,
            // will actually unset the env variable.
            $org_env = 'ALGOLIA_SETTINGS_FOLDER';
        } else {
            $org_env = 'ALGOLIA_SETTINGS_FOLDER=' . $org_env;
        }
        putenv('ALGOLIA_SETTINGS_FOLDER=custom-sub-path-settings/');

        $sut = new IndexRepository();

        $expected = [
            'title',
            'summary',
        ];

        $postSettings = $sut->getSettings((new TestModelWithSearchableTrait)->searchableAs());
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);

        putenv($org_env);
    }

    public function testValidateClassSearchableSuccessful()
    {
        $sut = new IndexRepository();

        $this->assertTrue($sut->validateClassSearchable(TestModelWithSearchableTrait::class));
    }

    public function testValidateClassSearchableTraitNotImplemented()
    {
        $sut = new IndexRepository();

        $this->assertFalse($sut->validateClassSearchable(TestModel::class));
    }

    public function testGetFilePathJustClass()
    {
        $sut = new IndexRepository();

        $expected = 'vfs://root/resources/algolia-settings/posts.json';

        $actual = $sut->getFilePath((new TestModelWithSearchableTrait)->searchableAs());

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePathExplicitlyAskSettings()
    {
        $sut = new IndexRepository();

        $expected = 'vfs://root/resources/algolia-settings/posts.json';

        $actual = $sut->getFilePath((new TestModelWithSearchableTrait)->searchableAs(), 'settings');

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePathRules()
    {
        $sut = new IndexRepository();

        $expected = 'vfs://root/resources/algolia-settings/posts-rules.json';

        $actual = $sut->getFilePath((new TestModelWithSearchableTrait)->searchableAs(), 'rules');

        $this->assertEquals($expected, $actual);
    }

    public function testGetFilePathSynonyms()
    {
        $sut = new IndexRepository();

        $expected = 'vfs://root/resources/algolia-settings/posts-synonyms.json';

        $actual = $sut->getFilePath((new TestModelWithSearchableTrait)->searchableAs(), 'synonyms');

        $this->assertEquals($expected, $actual);
    }
}
