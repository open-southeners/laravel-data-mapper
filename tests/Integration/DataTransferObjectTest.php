<?php

namespace OpenSoutheners\LaravelDto\Tests\Integration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Mockery;
use Mockery\MockInterface;
use Workbench\App\DataTransferObjects\CreatePostData;
use Workbench\App\DataTransferObjects\UpdatePostData;
use Workbench\App\DataTransferObjects\UpdatePostWithDefaultData;
use Workbench\App\Enums\PostStatus;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;
use Workbench\Database\Factories\FilmFactory;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\TagFactory;

use function OpenSoutheners\LaravelDto\map;

class DataTransferObjectTest extends TestCase
{
    public function test_data_transfer_object_from_request()
    {
        $user = (new User)->forceFill([
            'id' => 1,
            'name' => 'Ruben',
            'email' => 'ruben@hello.com',
            'password' => '',
        ]);

        $this->partialMock('auth', function (MockInterface $mock) use ($user) {
            $mock->expects('userResolver')->andReturn(fn () => $user);
        });

        /** @var CreatePostFormRequest */
        $mock = Mockery::mock(app(CreatePostFormRequest::class))->makePartial();

        $mock->shouldReceive('route')->andReturn('example');
        $mock->shouldReceive('validated')->andReturn([
            'title' => 'Hello world',
            'tags' => ['foo', 'bar', 'test'],
            'subscribers' => 'hello@world.com,hola@mundo.com,',
            'post_status' => PostStatus::Published->value,
        ]);

        // Not absolutely the same but does the job...
        app()->bind(Request::class, fn () => $mock);

        $data = map($mock)->to(CreatePostData::class);

        $this->assertTrue($data->postStatus instanceof PostStatus);
        $this->assertEquals('Hello world', $data->title);
        $this->assertIsArray($data->tags);
        $this->assertContains('bar', $data->tags);
        $this->assertTrue($data->subscribers instanceof Collection);
        $this->assertContains('hello@world.com', $data->subscribers->all());
        $this->assertContains('hola@mundo.com', $data->subscribers->all());
        $this->assertContains('bar', $data->tags);
        $this->assertTrue($user->is($data->currentUser));
    }

    public function test_data_transfer_object_from_array_with_models()
    {
        $post = Post::create([
            'id' => 1,
            'title' => 'Lorem ipsum',
            'slug' => 'lorem-ipsum',
            'status' => PostStatus::Hidden->value,
        ]);

        $data = map([
            'title' => 'Hello world',
            'tags' => 'foo,bar,test',
            'post_status' => PostStatus::Published->value,
            'post_id' => 1,
        ])->to(CreatePostData::class);

        $this->assertTrue($data->postStatus instanceof PostStatus);
        $this->assertEquals('Hello world', $data->title);
        $this->assertIsArray($data->tags);
        $this->assertContains('bar', $data->tags);
        $this->assertTrue($data->post->is($post));
    }

    public function test_data_transfer_object_filled_via_request()
    {
        $this->markTestSkipped('Need to reimplement filled method as a trait');

        /** @var CreatePostFormRequest */
        $mock = Mockery::mock(app(Request::class))->makePartial();

        $mock->shouldReceive('route')->andReturn('example');
        $mock->shouldReceive('all')->andReturn([
            'title' => 'Hello world',
            'tags' => '',
            'post_status' => PostStatus::Published->value,
        ]);
        $mock->shouldReceive('has')->withArgs(['post_status'])->andReturn(true);
        $mock->shouldReceive('has')->withArgs(['postStatus'])->andReturn(true);
        $mock->shouldReceive('has')->withArgs(['post'])->andReturn(false);

        $this->mock(Request::class, fn () => $mock);

        $data = map($mock)->to(CreatePostData::class);

        $this->assertFalse($data->filled('tags'));
        $this->assertTrue($data->filled('post_status'));
        $this->assertFalse($data->filled('post'));
    }

    public function test_data_transfer_object_without_property_keys_normalisation_when_disabled_from_config()
    {
        config(['data-mapper.normalise_properties' => false]);

        $post = Post::create([
            'id' => 2,
            'title' => 'Hello ipsum',
            'slug' => 'hello-ipsum',
            'status' => PostStatus::Hidden->value,
        ]);

        $parentPost = Post::create([
            'id' => 1,
            'title' => 'Lorem ipsum',
            'slug' => 'lorem-ipsum',
            'status' => PostStatus::Hidden->value,
        ]);

        $data = map([
            'post_id' => 2,
            'parent' => 1,
            'tags' => 'test,hello',
        ])->to(UpdatePostData::class);

        $this->assertTrue($data->post_id?->is($post));
        $this->assertTrue($data->parent?->is($parentPost));
    }

    public function test_data_transfer_object_with_default_value_attribute()
    {
        $this->markTestSkipped('To implement default values');

        $user = User::create([
            'email' => 'ruben@hello.com',
            'password' => '1234',
            'name' => 'Ruben',
        ]);

        $this->actingAs($user);

        $fooBarPost = PostFactory::new()->create([
            'title' => 'Foo bar',
            'slug' => 'foo-bar',
        ]);

        $helloWorldPost = PostFactory::new()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
        ]);

        Route::post('/posts/{post?}', function (UpdatePostWithDefaultData $data) {
            return response()->json((array) $data);
        });

        $response = $this->postJson('/posts', []);

        $response->assertJsonFragment([
            'author' => $user->toArray(),
            'post' => $helloWorldPost->toArray(),
        ]);
    }

    public function test_data_transfer_object_with_default_value_attribute_gets_bound_when_one_is_sent()
    {
        $user = User::create([
            'email' => 'ruben@hello.com',
            'password' => '1234',
            'name' => 'Ruben',
        ]);

        $this->actingAs($user);

        $fooBarPost = PostFactory::new()->create([
            'title' => 'Foo bar',
            'slug' => 'foo-bar',
        ]);

        $helloWorldPost = PostFactory::new()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
        ]);

        Route::post('/posts/{post}', function (UpdatePostWithDefaultData $data) {
            return response()->json((array) $data);
        });

        $response = $this->postJson('/posts/foo-bar', []);

        $response->assertJsonFragment([
            'author' => $user->toArray(),
            'post' => $fooBarPost->toArray(),
        ]);
    }

    public function test_data_transfer_object_with_morphs_gets_models_bound_of_each_type_sent()
    {
        $user = User::create([
            'email' => 'ruben@hello.com',
            'password' => '1234',
            'name' => 'Ruben',
        ]);

        $this->actingAs($user);

        $horrorTag = TagFactory::new()->create([
            'name' => 'Horror',
            'slug' => 'horror',
        ]);

        $fooBarPost = PostFactory::new()->create([
            'title' => 'Foo bar',
            'slug' => 'foo-bar',
        ]);

        $helloWorldPost = PostFactory::new()->create([
            'title' => 'Hello world',
            'slug' => 'hello-world',
        ]);

        $myFilm = FilmFactory::new()->create([
            'title' => 'My Film',
            'slug' => 'my-film',
            'year' => 1997,
        ]);

        $response = $this->patchJson('tags/1', [
            'name' => 'Scary',
            'taggable' => Collection::make([$myFilm->getKey(), $fooBarPost->getKey(), $helloWorldPost->getKey()])->join(', '),
            // TODO: Fix mapping by slug
            // 'taggable' => '1, foo-bar, hello-world',
            'taggable_type' => 'film, post',
        ]);

        $response->assertSuccessful();

        $response->assertJsonCount(3, 'data.taggable');

        $response->assertJsonFragment([
            'id' => 1,
            'title' => 'My Film',
            'year' => '1997',
            'about' => null,
        ]);

        $response->assertJsonFragment([
            'id' => 1,
            'title' => 'Foo bar',
            'slug' => 'foo-bar',
            'status' => 'published',
        ]);

        $response->assertJsonFragment([
            'id' => 2,
            'title' => 'Hello world',
            'slug' => 'hello-world',
            'status' => 'published',
        ]);
    }

    public function test_nested_data_transfer_objects_gets_the_nested_as_object_instance()
    {
        $this->markTestIncomplete('Need to create nested actions/DTOs');
    }

    public function test_data_transfer_object_does_not_take_route_bound_stuff()
    {
        $this->markTestIncomplete('Need to create nested actions/DTOs');
    }
}

class CreatePostFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ['string'],
            'tags' => ['string'],
            'post_status' => [Rule::enum(PostStatus::class)],
        ];
    }
}
