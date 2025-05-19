<?php

namespace OpenSoutheners\LaravelDto;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OpenSoutheners\LaravelDto\Commands\DtoMakeCommand;
use OpenSoutheners\LaravelDto\Commands\DtoTypescriptGenerateCommand;
use OpenSoutheners\LaravelDto\Contracts\ValidatedDataTransferObject;
use OpenSoutheners\LaravelDto\PropertyMappers;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/data-transfer-objects.php' => config_path('data-transfer-objects.php'),
            ], 'config');

            $this->commands([DtoMakeCommand::class, DtoTypescriptGenerateCommand::class]);
        }

        ObjectMapper::registerMapper([
            new PropertyMappers\ModelPropertyMapper,
            new PropertyMappers\CollectionPropertyMapper,
            new PropertyMappers\ObjectPropertyMapper,
            new PropertyMappers\GenericObjectPropertyMapper,
            new PropertyMappers\CarbonPropertyMapper,
            new PropertyMappers\BackedEnumPropertyMapper,
        ]);

        $this->app->bind(Authenticatable::class, fn ($app) => $app->get('auth')->user());
        
        // $this->app->beforeResolving(
        //     DataTransferObject::class,
        //     function ($dataClass, $parameters, $app) {
        //         /** @var \Illuminate\Foundation\Application $app */
        //         $app->scoped($dataClass, fn () => $dataClass::fromRequest(
        //             app(is_subclass_of($dataClass, ValidatedDataTransferObject::class) ? $dataClass::request() : Request::class)
        //         ));
        //     }
        // );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
