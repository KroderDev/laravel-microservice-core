<?php

namespace Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Orchestra\Testbench\TestCase;

class MakeModelCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    public function test_generates_remote_model()
    {
        $this->artisan('make:model', ['name' => 'RemoteUser', '--remote' => true])
            ->assertExitCode(0);

        $path = app_path('Models/RemoteUser.php');
        $this->assertFileExists($path);
        $contents = (new Filesystem())->get($path);
        $this->assertStringContainsString('Kroderdev\\LaravelMicroserviceCore\\Models\\Model', $contents);
    }
}
