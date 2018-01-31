<?php

namespace Algolia\Settings\Tests\Console;

use Algolia\Settings\Console\PushCommand;
use Algolia\Settings\IndexRepository;
use Algolia\Settings\Tests\TestModel;
use Algolia\Settings\Tests\TestModelWithSearchableTrait;
use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;
use Prophecy\Argument;

final class PushCommandTest extends TestCase
{
    private $file_system;

    public function setUp()
    {
        parent::setUp();

        // define my virtual file system
        $structure = [
            'resources' => [
                'algolia-settings' => [
                    'posts.json' => json_encode([
                        'searchableAttributes' => [
                            'title'
                        ],
                        'replicas'             => [
                            'posts_newest'
                        ],
                        'customRanking'        => null,
                    ]),
                    'posts_newest.json' => json_encode([
                        'searchableAttributes' => [
                            'title'
                        ],
                        'primary'              => 'posts',
                        'customRanking'        => [
                            'desc(published_at)'
                        ],
                    ]),
                    'posts-rules.json' => json_encode([
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
                    ]),
                    'posts-synonyms.json' => json_encode([
                        [
                            'objectID' => 'a-unique-identifier',
                            'type'     => 'synonym',
                            'synonyms' => ['car', 'vehicle', 'auto'],
                        ],
                    ]),
                ],
            ],
        ];

        // setup and cache the virtual file system
        $this->file_system = vfsStream::setup('root', null, $structure);

        $this->app->setBasePath($this->file_system->url());
    }

    public function testHandleSearchableModel()
    {
        $this->app[Kernel::class]->registerCommand(new PushCommand(new IndexRepository()));
        $this->app->bind(Client::class, function () {
            $clientProphet = $this->prophesize(Client::class);

            $postIndexProphet = $this->prophesize(Index::class);

            $clientProphet->initIndex(Argument::any())->willReturn($postIndexProphet->reveal());

            return $clientProphet->reveal();
        });

        $return_code = $this->artisan('algolia:settings:push', ['model' => TestModelWithSearchableTrait::class]);

        $this->assertEquals(0, $return_code);
    }

    public function testHandleNonSearchableModel()
    {
        $this->app[Kernel::class]->registerCommand(new PushCommand(new IndexRepository()));

        $return_code = $this->artisan('algolia:settings:push', ['model' => TestModel::class]);
        $cli_output = $this->app[Kernel::class]->output();

        $this->assertEquals(1, $return_code);
        $this->assertEquals(
            "The class [Algolia\Settings\Tests\TestModel] does not use the [Laravel\Scout\Searchable] trait\n",
            $cli_output
        );
    }
}
