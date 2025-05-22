<?php

namespace OpenSoutheners\LaravelDto\Tests\Integration;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class DtoMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'app/DataTransferObjects/CreatePostData.php',
    ];

    public function test_make_data_transfer_object_command_creates_basic_class_with_name()
    {
        $this->artisan('make:dto', ['name' => 'CreatePostData'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\DataTransferObjects;',
            'final class CreatePostData',
        ], 'app/DataTransferObjects/CreatePostData.php');
    }

    public function test_make_data_transfer_object_command_with_empty_request_option_creates_the_file_with_validated_request()
    {
        $this->artisan('make:dto', [
            'name' => 'CreatePostData',
            '--request' => true,
        ])->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\DataTransferObjects;',
            'final class CreatePostData',
            'implements ValidatedDataTransferObject',
            'public static function request(): string',
        ], 'app/DataTransferObjects/CreatePostData.php');
    }

    // TODO: Test properties from rules population
    public function test_make_data_transfer_object_command_with_request_option_creates_the_file_with_properties()
    {
        $this->artisan('make:dto', [
            'name' => 'CreatePostData',
            '--request' => 'Workbench\App\Http\Requests\PostCreateFormRequest',
        ])->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\DataTransferObjects;',
            'use Workbench\App\Http\Requests\PostCreateFormRequest;',
            'final class CreatePostData',
            'implements ValidatedDataTransferObject',
            'return PostCreateFormRequest::class;',
        ], 'app/DataTransferObjects/CreatePostData.php');
    }
}
