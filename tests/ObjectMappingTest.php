<?php

namespace OpenSoutheners\LaravelDataMapper\Tests;

use Workbench\App\DataObjects\CreateUserData;

use function OpenSoutheners\LaravelDataMapper\map;

class ObjectMappingTest extends TestCase
{
    public function testMappingToObjectFromArray()
    {
        $data = map([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])->to(CreateUserData::class);

        $this->assertIsString($data->name);
        $this->assertIsString($data->email);
        
        $this->assertEquals('John Doe', $data->name);
        $this->assertEquals('john@example.com', $data->email);
    }
    
    public function testMappingToObjectFromJsonString()
    {
        $data = map(json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]))->to(CreateUserData::class);

        $this->assertIsString($data->name);
        $this->assertIsString($data->email);
        
        $this->assertEquals('John Doe', $data->name);
        $this->assertEquals('john@example.com', $data->email);
    }
}
