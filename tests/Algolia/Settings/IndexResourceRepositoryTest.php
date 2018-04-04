<?php

namespace Algolia\Settings\Tests\Algolia\Settings;

use Algolia\Settings\IndexName;
use Algolia\Settings\IndexResourceRepository;
use Algolia\Settings\Tests\TestModelWithSearchableTrait;
use Algolia\Settings\Tests\TestCase;
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
        "testing_posts_newest",
        "testing_posts_oldest"
    ],
    "customRanking": null
}
POST
                    ,
                    'posts_newest.json' => <<<'POST'
{
    "searchableAttributes": [
        "title"
    ],
    "primary": "testing_posts",
    "customRanking": [
        "desc(published_at)"
    ]
}
POST
                    ,
                    'posts_oldest.json' => <<<'POST'
{
    "searchableAttributes": [
        "title"
    ],
    "primary": "testing_posts",
    "customRanking": [
        "asc(published_at)"
    ]
}
POST
                    ,
                    'posts-rules.json' => <<<'POST'
[
    {
        "objectID"    : "a-rule-id",
        "condition"   : {
            "pattern"   : "smartphone",
            "anchoring" : "contains"
        },
        "consequence" : {
            "params" : {
                "filters" : "category = 1"
            }
        }
    }
]
POST
                    ,'posts-synonyms.json' => <<<'POST'
[
   {
      "objectID":"a-unique-identifier",
      "type":"synonym",
      "synonyms":[
         "car",
         "vehicle",
         "auto"
      ]
   }
]
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

        $postSettings = $sut->getSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false));
        $actual = $postSettings['searchableAttributes'];

        $this->assertEquals($expected, $actual);
    }

    public function testGetSettingsCustomLocation()
    {
        try {
            $org_envs = $this->replaceEnvs(['ALGOLIA_SETTINGS_FOLDER' => 'custom-sub-path-settings/']);

            $sut = new IndexResourceRepository();

            $expected = [
                'title',
                'summary',
            ];

            $postSettings = $sut->getSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false));
            $actual = $postSettings['searchableAttributes'];

            $this->assertEquals($expected, $actual);
        } finally {
            $this->restoreEnvs($org_envs);
        }
    }

    public function testGetSettingsUnknownIndex()
    {
        $sut = new IndexResourceRepository();

        $actual = $sut->getSettings(IndexName::createFromRemote('foobar', false));

        $this->assertEquals([], $actual);
    }

    public function testCreationSettingsDirectory()
    {
        try {
            $org_envs = $this->replaceEnvs(['ALGOLIA_SETTINGS_FOLDER' => 'non-existing-directory/']);

            $this->assertFalse($this->file_system->hasChild('resources/non-existing-directory'));

            new IndexResourceRepository();

            $this->assertTrue($this->file_system->hasChild('resources/non-existing-directory'));

        } finally {
            $this->restoreEnvs($org_envs);
        }
    }

    public function testSaveSettingsPrimaryUsingPrefix()
    {
        $settings = [
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'replicas'             => [
                'testing_posts_newest',
                'testing_posts_oldest',
            ],
            'customRanking'        => null,
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), true), $settings);
        $this->assertNotFalse($actual);

        $expected = \json_encode($settings);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/testing_posts.json')->getContent()
        );
    }

    public function testSaveSettingsPrimaryWithoutPrefix()
    {
        $settings = [
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'replicas'             => [
                'testing_posts_newest',
                'testing_posts_oldest',
            ],
            'customRanking'        => null,
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false), $settings);
        $this->assertNotFalse($actual);

        $expected = \json_encode([
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
        ]);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/posts.json')->getContent()
        );
    }

    public function testSaveSettingsReplicaUsingPrefix()
    {
        $settings = [
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'primary'             => 'testing_posts',
            'customRanking'        => null,
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), true), $settings);
        $this->assertNotFalse($actual);

        $expected = \json_encode($settings);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/testing_posts.json')->getContent()
        );
    }

    public function testSaveSettingsReplicaWithoutPrefix()
    {
        $settings = [
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'primary'             => 'testing_posts',
            'customRanking'        => null,
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSettings(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false), $settings);
        $this->assertNotFalse($actual);

        $expected = \json_encode([
            'searchableAttributes' => [
                'title',
                'summary',
                'tags',
            ],
            'primary'             => 'posts',
            'customRanking'        => null,
        ]);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/posts.json')->getContent()
        );
    }

    public function testGetRules()
    {
        $sut = new IndexResourceRepository();

        $expected = [
            [
                'objectID'    => 'a-rule-id',
                'condition'   => [
                    'pattern'   => 'smartphone',
                    'anchoring' => 'contains'
                ],
                'consequence' => [
                    'params' => [
                        'filters' => 'category = 1',
                    ],
                ],
            ],
        ];

        $actual = $sut->getRules(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false));

        $this->assertEquals($expected, $actual);
    }

    public function testSaveRules()
    {
        $rules = [
            [
                'objectID'    => 'a-better-rule-id',
                'condition'   => [
                    'pattern'   => 'tablets',
                    'anchoring' => 'contains'
                ],
                'consequence' => [
                    'params' => [
                        'filters' => 'category = 2',
                    ],
                ],
            ],
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveRules(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false), $rules);
        $expected = \json_encode($rules);

        $this->assertNotFalse($actual);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/posts-rules.json')->getContent()
        );
    }

    public function testSaveSynonyms()
    {
        $synonyms = [
            [
                'objectID' => 'a-unique-identifier',
                'type'     => 'synonym',
                'synonyms' => ['car', 'vehicle', 'auto'],
            ],
            [
                'objectID' => 'another-unique-identifier',
                'type'     => 'synonym',
                'synonyms' => ['developer', 'programmer', 'software engineer'],
            ],
        ];

        $sut = new IndexResourceRepository();

        $actual = $sut->saveSynonyms(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false), $synonyms);
        $expected = \json_encode($synonyms);

        $this->assertNotFalse($actual);
        $this->assertJsonStringEqualsJsonString(
            $expected,
            $this->file_system->getChild('resources/algolia-settings/posts-synonyms.json')->getContent()
        );
    }

    public function testGetSynonyms()
    {
        $sut = new IndexResourceRepository();

        $expected = [
            [
                'objectID' => 'a-unique-identifier',
                'type'     => 'synonym',
                'synonyms' => ['car', 'vehicle', 'auto'],
            ],
        ];

        $actual = $sut->getSynonyms(IndexName::createFromRemote((new TestModelWithSearchableTrait)->searchableAs(), false));

        $this->assertEquals($expected, $actual);
    }
}
