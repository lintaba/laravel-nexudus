<?php

namespace Lintaba\LaravelNexudus\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lintaba\LaravelNexudus\LaravelNexudusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Lintaba\\LaravelNexudus\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelNexudusServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-nexudus_table.php.stub';
        $migration->up();
        */
    }
}
