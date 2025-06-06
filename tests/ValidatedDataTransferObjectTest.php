<?php

namespace OpenSoutheners\LaravelDataMapper\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Workbench\App\DataTransferObjects\UpdatePostWithRouteBindingData;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\TagFactory;

use function OpenSoutheners\LaravelDataMapper\map;

class ValidatedDataTransferObjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function test_validated_data_transfer_object_gets_route_bound_model()
    {
        $post = PostFactory::new()->hasAttached(
            TagFactory::new()->count(2)
        )->create();

        $response = $this->patchJson('post/1', []);

        $response->assertJson([
            'post' => $post->load('tags')->toArray(),
        ], true);
    }

    public function test_validated_data_transfer_object_gets_validated_only_parameters()
    {
        PostFactory::new()->create();

        $firstTag = TagFactory::new()->create();
        $secondTag = TagFactory::new()->create();

        $response = $this->patchJson('post/1', [
            'tags' => '1,2',
            'post_status' => 'test_non_existing_status',
            'published_at' => '2023-09-06 17:35:53',
        ]);

        $response->assertJson([
            'tags' => [
                [
                    'id' => $firstTag->getKey(),
                    'name' => $firstTag->name,
                    'slug' => $firstTag->slug,
                ],
                [
                    'id' => $secondTag->getKey(),
                    'name' => $secondTag->name,
                    'slug' => $secondTag->slug,
                ],
            ],
            'publishedAt' => '2023-09-06T17:35:53.000000Z',
        ], true);
    }

    public function test_data_transfer_object_with_model_sent_does_load_relationship_if_missing()
    {
        $post = PostFactory::new()->hasAttached(
            TagFactory::new()->count(2)
        )->create();

        $data = map([
            'post' => $post,
        ])->to(UpdatePostWithRouteBindingData::class);

        DB::enableQueryLog();

        $this->assertTrue($data->post->relationLoaded('tags'));
        $this->assertNotEmpty($data->post->tags);
        $this->assertCount(2, $data->post->tags);
        $this->assertEmpty(DB::getQueryLog());
    }

    public function test_data_transfer_object_with_model_sent_does_not_run_queries_to_fetch_it_again()
    {
        $post = PostFactory::new()->make();

        $post->setRelation('tags', []);

        DB::enableQueryLog();

        $data = map([
            'post' => $post,
        ])->to(UpdatePostWithRouteBindingData::class);

        $this->assertEmpty(DB::getQueryLog());
        $this->assertTrue($data->post->is($post));
    }

    public function test_data_transfer_object_can_be_serialized_and_deserialized()
    {
        $this->withoutExceptionHandling();

        PostFactory::new()->create();

        TagFactory::new()->create();
        TagFactory::new()->create();

        $data = map([
            'post' => '1',
            'tags' => '1,2',
            'post_status' => 'test_non_existing_status',
            'published_at' => '2023-09-06 17:35:53',
        ])->to(UpdatePostWithRouteBindingData::class);

        $serializedData = serialize($data);

        $this->assertIsString($serializedData);

        $deserializedData = unserialize($serializedData);

        $this->assertTrue($data->post->is($deserializedData->post));
        $this->assertTrue($deserializedData->post->relationLoaded('tags'));
        $this->assertTrue($data->tags->first()->is($deserializedData->tags->first()));
        $this->assertTrue($data->publishedAt->eq($deserializedData->publishedAt));
    }
}
