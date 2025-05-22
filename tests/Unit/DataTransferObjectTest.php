<?php

namespace OpenSoutheners\LaravelDto\Tests\Unit;

use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use OpenSoutheners\LaravelDto\ObjectMapper;
use OpenSoutheners\LaravelDto\PropertyMappers;
use PHPUnit\Framework\TestCase;
use Workbench\App\DataTransferObjects\CreateComment;
use Workbench\App\DataTransferObjects\CreateManyPostData;
use Workbench\App\DataTransferObjects\CreatePostData;
use Workbench\App\Enums\PostStatus;

use function OpenSoutheners\LaravelDto\map;

class DataTransferObjectTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        
        ObjectMapper::registerMapper([
            new PropertyMappers\ModelPropertyMapper,
            new PropertyMappers\CollectionPropertyMapper,
            new PropertyMappers\ObjectPropertyMapper,
            new PropertyMappers\GenericObjectPropertyMapper,
            new PropertyMappers\CarbonPropertyMapper,
            new PropertyMappers\BackedEnumPropertyMapper,
        ]);

        $mockedConfig = Mockery::mock(Repository::class);

        $mockedConfig->shouldReceive('get')->andReturn(true);

        Container::getInstance()->bind('config', fn () => $mockedConfig);

        $mockedAuth = Mockery::mock(AuthManager::class);

        $mockedAuth->shouldReceive('check')->andReturn(false);
        $mockedAuth->shouldReceive('userResolver')->andReturn(fn () => null);

        Container::getInstance()->bind('auth', fn () => $mockedAuth);

        Container::getInstance()->bind('dto.context.booted', fn () => '');
    }

    public function testDataTransferObjectFromArray()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => 'foo,bar,test',
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertTrue($data->postStatus instanceof PostStatus);
        $this->assertEquals('Hello world', $data->title);
        $this->assertIsArray($data->tags);
        $this->assertContains('bar', $data->tags);
        $this->assertNull($data->post);
    }

    public function testDataTransferObjectFromArrayDelimitedLists()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => 'foo',
            'country' => 'foo',
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertIsArray($data->tags);
        $this->assertIsString($data->country);
    }

    public function testDataTransferObjectFilledViaClassProperties()
    {
        $this->markTestSkipped('To implement filled method as trait');
        
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'author_email' => 'me@d8vjork.com',
        ])->to(CreatePostData::class);

        $this->assertTrue($data->filled('tags'));
        $this->assertTrue($data->filled('postStatus'));
        $this->assertFalse($data->filled('post'));
        $this->assertFalse($data->filled('description'));
        $this->assertFalse($data->filled('author_email'));
    }

    public function testDataTransferObjectWithDefaults()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertContains('generic', $data->tags);
        $this->assertContains('post', $data->tags);
    }

    public function testDataTransferObjectArrayWithoutTypedPropertiesGetsThroughWithoutChanges()
    {
        $helloTag = [
            'name' => 'Hello world',
            'slug' => 'hello-world',
        ];

        $travelingTag = [
            'name' => 'Traveling guides',
            'slug' => 'traveling-guides',
        ];

        $data = map([
            'title' => 'Hello world',
            'tags' => [
                $helloTag,
                $travelingTag,
            ],
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertContains($helloTag, $data->tags);
        $this->assertContains($travelingTag, $data->tags);
    }

    public function testDataTransferObjectArrayPropertiesGetsMappedAsCollection()
    {
        $rubenUser = [
            'name' => 'Rubén Robles',
            'email' => 'ruben@hello.com',
        ];

        $taylorUser = [
            'name' => 'Taylor Otwell',
            'email' => 'taylor@hello.com',
        ];

        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'subscribers' => [
                $rubenUser,
                $taylorUser,
            ],
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertTrue($data->subscribers instanceof Collection);
        $this->assertContains($rubenUser, $data->subscribers);
        $this->assertContains($taylorUser, $data->subscribers);
    }

    public function testDataTransferObjectDatePropertiesGetMappedFromStringsIntoCarbonInstances()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'published_at' => '2023-09-06 17:35:53',
            'content' => '{"type": "doc", "content": [{"type": "paragraph", "attrs": {"textAlign": "left"}, "content": [{"text": "dede", "type": "text"}]}]}',
        ])->to(CreatePostData::class);

        $this->assertTrue($data->publishedAt instanceof Carbon);
        $this->assertTrue(now()->isAfter($data->publishedAt));
    }

    public function testDataTransferObjectDatePropertiesGetMappedFromJsonStringsIntoGenericObjects()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'content' => '{"type": "doc", "content": [{"type": "paragraph", "attrs": {"textAlign": "left"}, "content": [{"text": "hello world", "type": "text"}]}]}',
        ])->to(CreatePostData::class);

        $this->assertTrue($data->content instanceof \stdClass);
        $this->assertObjectHasProperty('type', $data->content);
    }

    public function testDataTransferObjectDatePropertiesGetMappedFromArraysIntoGenericObjects()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'content' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'attrs' => [
                            'textAlign' => 'left',
                        ],
                        'content' => [
                            [
                                'text' => 'hello world',
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
        ])->to(CreatePostData::class);

        $this->assertTrue($data->content instanceof \stdClass);
        $this->assertObjectHasProperty('type', $data->content);
    }

    public function testDataTransferObjectDatePropertiesGetMappedFromArraysOfObjectsIntoCollectionOfGenericObjects()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'dates' => [
                '2023-09-06 17:35:53',
                '2023-09-07 06:35:53',
            ],
        ])->to(CreatePostData::class);

        $this->assertTrue($data->dates instanceof Collection);
        $this->assertTrue($data->dates->first() instanceof Carbon);
        $this->assertTrue(now()->isAfter($data->dates->first()));
        $this->assertTrue(now()->isAfter($data->dates->last()));
    }

    public function testDataTransferObjectDatePropertiesDoesNotGetMappedFromCollectionsToSameType()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
            'dates' => Collection::make([
                '2023-09-06 17:35:53',
                '2023-09-07 06:35:53',
            ]),
        ])->to(CreatePostData::class);

        $this->assertTrue($data->dates instanceof Collection);
        $this->assertFalse($data->dates->first() instanceof Carbon);
        $this->assertIsString($data->dates->first());
    }

    public function testDataTransferObjectSentIntoAnotherAsCollectedWillBeMappedFromArray()
    {
        $data = map([
            'posts' => [
                [
                    'title' => 'Hello world',
                    'tags' => '',
                    'post_status' => PostStatus::Published->value,
                    'dates' => [
                        '2023-09-06 17:35:53',
                        '2023-09-07 06:35:53',
                    ],
                ],
                [
                    'title' => 'Foo bar',
                    'tags' => '',
                    'post_status' => PostStatus::Hidden->value,
                    'dates' => [
                        '2024-01-06 13:35:53',
                        '2023-02-07 05:35:53',
                    ],
                ],
            ],
        ])->to(CreateManyPostData::class);

        $this->assertInstanceOf(Collection::class, $data->posts);

        $this->assertInstanceOf(CreatePostData::class, $data->posts->first());
        $this->assertEquals('Hello world', $data->posts->first()->title);
        $this->assertInstanceOf(Collection::class, $data->posts->first()->dates);
        $this->assertInstanceOf(Carbon::class, $data->posts->first()->dates->first());

        $this->assertInstanceOf(CreatePostData::class, $data->posts->last());
        $this->assertEquals('Foo bar', $data->posts->last()->title);
        $this->assertInstanceOf(Collection::class, $data->posts->last()->dates);
        $this->assertInstanceOf(Carbon::class, $data->posts->last()->dates->first());
    }

    public function testDataTransferObjectRetainKeysFromNestedObjectsOrArrays()
    {
        $data = map([
            'content' => 'hello world',
            'tags' => [
                'hello' => 'world',
                'foo' => 'bar'
            ]
        ])->to(CreateComment::class);

        $this->assertArrayHasKey('hello', $data->tags);
        $this->assertArrayHasKey('foo', $data->tags);
    }
}
