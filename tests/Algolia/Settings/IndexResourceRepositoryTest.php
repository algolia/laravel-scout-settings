<?php

namespace Algolia\Settings\Tests\Algolia\Settings;

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

        $postSettings = $sut->getSettings((new TestModelWithSearchableTrait)->searchableAs());
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

            $postSettings = $sut->getSettings((new TestModelWithSearchableTrait)->searchableAs());
            $actual = $postSettings['searchableAttributes'];

            $this->assertEquals($expected, $actual);
        } finally {
            $this->restoreEnvs($org_envs);
        }
    }

    public function testGetSettingsUnknownIndex()
    {
        $sut = new IndexResourceRepository();

        $actual = $sut->getSettings('foobar');

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

    public function testSaveSettings()
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

        $actual = $sut->saveSettings((new TestModelWithSearchableTrait)->searchableAs(), $settings);
        $expected = \json_encode($settings);

        $this->assertNotFalse($actual);
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

        $actual = $sut->getRules((new TestModelWithSearchableTrait)->searchableAs());

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

        $actual = $sut->saveRules((new TestModelWithSearchableTrait)->searchableAs(), $rules);
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

        $actual = $sut->saveSynonyms((new TestModelWithSearchableTrait)->searchableAs(), $synonyms);
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

        $actual = $sut->getSynonyms((new TestModelWithSearchableTrait)->searchableAs());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider prefixOptionProvider
     */
    public function testUsePrefix($prefixOption, $expected)
    {
        $sut = new IndexResourceRepository();

        $sut->usePrefix($prefixOption);

        $reflected_property = new \ReflectionProperty(get_class($sut), 'usePrefix');
        $reflected_property->setAccessible(true);
        $actual = $reflected_property->getValue($sut);

        $this->assertEquals($expected, $actual);
    }

    public function prefixOptionProvider()
    {
        return [
            [true, true],
            [false, false],
        ];
    }
}
