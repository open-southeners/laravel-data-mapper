<?php

namespace OpenSoutheners\LaravelDto\Tests\Unit;

use OpenSoutheners\LaravelDto\Tests\Integration\TestCase;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

use function OpenSoutheners\LaravelDto\map;

class MapperTest extends TestCase
{
    public function testMapNumericIdToModelResultsInModelInstance()
    {
        $user = UserFactory::new()->create();
        
        $result = map('1')->to(User::class);
        
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->email, $result->email);
    }
}
