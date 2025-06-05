<?php

namespace OpenSoutheners\LaravelDataMapper\Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenSoutheners\LaravelDataMapper\Tests\Integration\TestCase;
use Workbench\App\DataTransferObjects\CreateComment;
use Workbench\App\DataTransferObjects\CreateManyPostData;
use Workbench\App\DataTransferObjects\CreatePostData;
use Workbench\App\Enums\PostStatus;

use function OpenSoutheners\LaravelDataMapper\map;

class DataTransferObjectTest extends TestCase
{
    public function test_object_as_data_transfer_object_from_array()
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

    public function test_data_transfer_object_from_array_delimited_lists()
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

    public function test_data_transfer_object_filled_via_class_properties()
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

    public function test_data_transfer_object_with_defaults()
    {
        $data = map([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
        ])->to(CreatePostData::class);

        $this->assertContains('generic', $data->tags);
        $this->assertContains('post', $data->tags);
    }

    public function test_data_transfer_object_array_without_typed_properties_gets_through_without_changes()
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

    public function test_data_transfer_object_array_properties_gets_mapped_as_collection()
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

    public function test_data_transfer_object_date_properties_get_mapped_from_strings_into_carbon_instances()
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

    public function test_data_transfer_object_date_properties_get_mapped_from_json_strings_into_generic_objects()
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

    public function test_data_transfer_object_date_properties_get_mapped_from_arrays_into_generic_objects()
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

    public function test_data_transfer_object_date_properties_get_mapped_from_arrays_of_objects_into_collection_of_generic_objects()
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

    public function test_data_transfer_object_date_properties_does_not_get_mapped_from_collections_to_same_type()
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
        $this->assertTrue($data->dates->first() instanceof Carbon);
        $this->assertTrue($data->dates->last() instanceof Carbon);
    }

    public function test_data_transfer_object_sent_into_another_as_collected_will_be_mapped_from_array()
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

    public function test_data_transfer_object_retain_keys_from_nested_objects_or_arrays()
    {
        $data = map([
            'content' => 'hello world',
            'tags' => [
                'hello' => 'world',
                'foo' => 'bar',
            ],
        ])->to(CreateComment::class);

        $this->assertArrayHasKey('hello', $data->tags);
        $this->assertArrayHasKey('foo', $data->tags);
    }
}
