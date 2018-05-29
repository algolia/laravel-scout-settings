<?php

namespace Ubeeqo\Algolia\Settings\Tests\Algolia\Settings;

use Ubeeqo\Algolia\Settings\IndexResourceRepository;
use Ubeeqo\Algolia\Settings\Tests\TestModel;
use Ubeeqo\Algolia\Settings\Tests\TestModelWithSearchableTrait;
use Laravel\Scout\Searchable;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;

final class IndexResourceRepositoryTest extends TestCase
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
        $sut = new IndexResourceRepository();

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

        $sut = new IndexResourceRepository();

        $expected = [
            'title',
            'summary',
        ];

        $postSettings = $sut->getSettings((new TestModelWithSearchableTrait)->searchableAs());
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);

        putenv($org_env);
    }

    public function testSaveSettings()
    {
        $settings = [
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'replicas'             => [
                'posts_newest',
                'posts_oldest',
            ],
            'customRanking'        => null,
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSettings((new TestModelWithSearchableTrait)->searchableAs(), $settings);
        $expected = \json_encode($settings);

        $this->assertNotFalse($actual);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/posts.json')->getContent()
        );
    }
}
