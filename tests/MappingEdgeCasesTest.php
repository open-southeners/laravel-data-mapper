<?php

namespace OpenSoutheners\LaravelDataMapper\Tests;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

use function OpenSoutheners\LaravelDataMapper\map;

class MappingEdgeCasesTest extends TestCase
{
    public function test_input_keys_naming_model_methods_are_not_invoked()
    {
        $user = map(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret', 'save' => 1])
            ->to(User::class);

        $this->assertFalse($user->exists);
        $this->assertSame(0, User::count());
    }

    public function test_list_of_scalars_property_keeps_items_as_given()
    {
        $result = map(['ids' => [0, 1, 2], 'names' => ['a', 'b']])->to(EdgeCaseScalarListDto::class);

        $this->assertSame([0, 1, 2], $result->ids);
        $this->assertSame(['a', 'b'], $result->names);
    }

    public function test_comma_separated_string_to_list_of_scalars_property()
    {
        $result = map(['names' => 'a,,b'])->to(EdgeCaseScalarListDto::class);

        $this->assertSame(['a', 'b'], array_values($result->names));
    }

    public function test_falsy_items_other_than_null_are_kept()
    {
        $result = map([0, 1, '', '0', false, null])->to(Collection::class);

        $this->assertSame([0, 1, '', '0', false], $result->values()->all());
    }

    public function test_single_id_to_array_of_models_property_resolves_only_that_model()
    {
        UserFactory::new()->count(3)->create();

        $result = map(['users' => '2'])->to(EdgeCaseModelListDto::class);

        $this->assertCount(1, $result->users);
        $this->assertSame(2, $result->users[0]->id);
    }

    public function test_single_id_to_collection_of_models_property_resolves_only_that_model()
    {
        UserFactory::new()->count(3)->create();

        $result = map(['members' => '2'])->to(EdgeCaseModelListDto::class);

        $this->assertCount(1, $result->members);
        $this->assertInstanceOf(User::class, $result->members->first());
        $this->assertSame(2, $result->members->first()->id);
    }

    public function test_unknown_single_id_to_array_of_models_property_resolves_empty_list()
    {
        $result = map(['users' => '99'])->to(EdgeCaseModelListDto::class);

        $this->assertSame([], $result->users);
    }

    public function test_collection_subclass_target_returns_that_class()
    {
        $result = map([1, 2])->to(EdgeCaseCollection::class);

        $this->assertInstanceOf(EdgeCaseCollection::class, $result);
        $this->assertSame([1, 2], $result->all());
    }

    public function test_array_to_eloquent_collection_target()
    {
        $result = map([1, 2])->to(EloquentCollection::class);

        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame([1, 2], $result->all());
    }

    public function test_public_property_outside_constructor_is_set_after_construction()
    {
        $result = map(['name' => 'n', 'note' => 'set later', 'locked' => 'ignored'])->to(EdgeCaseExtraPropertyDto::class);

        $this->assertSame('n', $result->name);
        $this->assertSame('set later', $result->note);
        $this->assertFalse((new \ReflectionProperty($result, 'locked'))->isInitialized($result));
    }

    public function test_snake_case_id_key_maps_to_camel_case_id_property()
    {
        $result = map(['user_id' => 5, 'author_id' => 'x'])->to(EdgeCaseSnakeIdDto::class);

        $this->assertSame(5, $result->userId);
        $this->assertSame('x', $result->authorId);
    }
}

class EdgeCaseScalarListDto
{
    /**
     * @param  array<int>  $ids
     * @param  array<string>  $names
     */
    public function __construct(public array $ids = [], public array $names = [])
    {
        //
    }
}

class EdgeCaseModelListDto
{
    /**
     * @param  array<User>  $users
     * @param  Collection<int, User>|null  $members
     */
    public function __construct(public array $users = [], public ?Collection $members = null)
    {
        //
    }
}

class EdgeCaseExtraPropertyDto
{
    public ?string $note = null;

    public readonly string $locked;

    public function __construct(public string $name)
    {
        //
    }
}

class EdgeCaseSnakeIdDto
{
    public function __construct(public ?int $userId = null, public ?string $authorId = null)
    {
        //
    }
}

class EdgeCaseCollection extends Collection
{
    //
}
