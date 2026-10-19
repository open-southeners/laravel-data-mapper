<?php

namespace OpenSoutheners\LaravelDataMapper\Tests;

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
