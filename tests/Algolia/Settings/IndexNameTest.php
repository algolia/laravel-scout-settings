<?php

namespace Tests\Algolia\Settings;

use Algolia\Settings\IndexName;
use Algolia\Settings\Tests\TestCase;

final class IndexNameTest extends TestCase
{

    /**
     * @dataProvider createFromRemoteDataProvider
     */
    public function testCreateFromRemote($remoteName, $usePrefix)
    {
        $this->assertInstanceOf(IndexName::class, IndexName::createFromRemote($remoteName, $usePrefix));
    }

    public function createFromRemoteDataProvider()
    {
        return [
            ['posts', true],
            ['posts_newest', true],
            ['testing_posts', true],
            ['testing_really Any kind of name as an index', true],
            ['testing_posts', false],
        ];
    }

    /**
     * @dataProvider createReplicaDataProvider
     */
    public function testCreateFromLocal($indexName, $usePrefix)
    {
        $this->assertInstanceOf(IndexName::class, IndexName::createFromLocal($indexName, $usePrefix));
    }

    public function createReplicaDataProvider()
    {
        return [
            ['posts_newest', true],
            ['posts_newest', false],
        ];
    }

    /**
     * @dataProvider localDataProvider
     */
    public function testLocal($factoryMethod, $indexName, $usePrefix, $expected)
    {
        $sut = IndexName::{$factoryMethod}($indexName, $usePrefix);

        $this->assertEquals($expected, $sut->local());
    }

    public function localDataProvider()
    {
        return [
            ['createFromRemote', 'posts', true, 'posts'],
            ['createFromRemote', 'posts', false, 'posts'],
            ['createFromRemote', 'testing_posts', true, 'testing_posts'],
            ['createFromRemote', 'testing_posts', false, 'posts'],
            ['createFromLocal', 'posts_newest', true, 'testing_posts_newest'],
            ['createFromLocal', 'posts_newest', false, 'posts_newest'],
            ['createFromLocal', 'some Completely different index name', true, 'testing_some Completely different index name'],
            ['createFromLocal', 'some Completely different index name', false, 'some Completely different index name'],
        ];
    }

    /**
     * @dataProvider remoteDataProvider
     */
    public function testRemote($factoryMethod, $indexName, $usePrefix, $expected)
    {
        $sut = IndexName::{$factoryMethod}($indexName, $usePrefix);

        $this->assertEquals($expected, $sut->remote());
    }

    public function remoteDataProvider()
    {
        return [
            ['createFromRemote', 'posts', true, 'posts'],
            ['createFromRemote', 'posts', false, 'posts'],
            ['createFromRemote', 'testing_posts', true, 'testing_posts'],
            ['createFromRemote', 'testing_posts', false, 'testing_posts'],
            ['createFromLocal', 'posts_newest', true, 'testing_posts_newest'],
            ['createFromLocal', 'posts_newest', false, 'testing_posts_newest'],
            ['createFromLocal', 'some Completely different index name', true, 'testing_some Completely different index name'],
            ['createFromLocal', 'some Completely different index name', false, 'testing_some Completely different index name'],
        ];
    }

    /**
     * @dataProvider usesPrefixDataProvider
     */
    public function testUsesPrefix($indexName, $usePrefix, $assertMethod)
    {
        $sut = IndexName::createFromRemote($indexName, $usePrefix);

        $this->{$assertMethod}($sut->usesPrefix());
    }

    public function usesPrefixDataProvider()
    {
        return [
            ['posts', true, 'assertTrue'],
            ['posts', false, 'assertFalse'],
        ];
    }
}
