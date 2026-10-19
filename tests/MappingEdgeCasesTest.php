<?php

namespace OpenSoutheners\LaravelDataMapper\Tests;

use Workbench\App\Models\User;

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
}
