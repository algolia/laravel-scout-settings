<?php

namespace Algolia\Settings\Tests\Console;

use Algolia\Settings\Console\BackupCommand;
use Algolia\Settings\IndexRepository;
use Algolia\Settings\Tests\TestModel;
use Algolia\Settings\Tests\TestModelWithSearchableTrait;
use AlgoliaSearch\Client;
use AlgoliaSearch\Index;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;
use org\bovigo\vfs\vfsStream;

final class BackupCommandTest extends TestCase
{
    private $file_system;

    public function setUp()
    {
        parent::setUp();

        // define my virtual file system
        $structure = [
            'resources' => []
        ];

        // setup and cache the virtual file system
        $this->file_system = vfsStream::setup('root', null, $structure);

        $this->app->setBasePath($this->file_system->url());
    }

    public function testHandleSearchableModel()
    {
        $this->app[Kernel::class]->registerCommand(new BackupCommand(new IndexRepository()));
        $this->app->bind(Client::class, function () {
            $clientProphet = $this->prophesize(Client::class);

            $postIndexProphet = $this->prophesize(Index::class);
            $postIndexProphet->getSettings()->willReturn([
                'searchableAttributes' => [
                    'title'
                ],
                'replicas'             => [
                    'posts_newest'
                ],
                'customRanking'        => null,
            ]);
            $postIndexProphet->initSynonymIterator()->willReturn([]);
            $postIndexProphet->initRuleIterator()->willReturn([]);

            $clientProphet->initIndex('posts')->willReturn($postIndexProphet->reveal());

            $postNewestIndexProphet = $this->prophesize(Index::class);
            $postNewestIndexProphet->getSettings()->willReturn([
                'searchableAttributes' => [
                    'title'
                ],
                'primary'              => 'posts',
                'customRanking'        => [
                    'desc(published_at)'
                ],
            ]);
            $clientProphet->initIndex('posts_newest')->willReturn($postNewestIndexProphet->reveal());

            return $clientProphet->reveal();
        });

        $this->artisan('algolia:settings:backup', ['model' => TestModelWithSearchableTrait::class]);

        $this->assertFileExists($this->file_system->url() . '/resources/algolia-settings/posts.json');
        $this->assertFileExists($this->file_system->url() . '/resources/algolia-settings/posts_newest.json');
        $this->assertFileExists($this->file_system->url() . '/resources/algolia-settings/posts-rules.json');
        $this->assertFileExists($this->file_system->url() . '/resources/algolia-settings/posts-synonyms.json');
    }

    public function testHandleNonSearchableModel()
    {
        $this->app[Kernel::class]->registerCommand(new BackupCommand(new IndexRepository()));

        $return_code = $this->artisan('algolia:settings:backup', ['model' => TestModel::class]);
        $cli_output = $this->app[Kernel::class]->output();

        $this->assertEquals(1, $return_code);
        $this->assertEquals("The class [Algolia\Settings\Tests\TestModel] does not use the [Laravel\Scout\Searchable] trait\n", $cli_output);
    }
}
