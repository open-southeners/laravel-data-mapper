<?php

namespace OpenSoutheners\LaravelDataMapper;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use OpenSoutheners\LaravelDataMapper\Attributes\Validate;
use OpenSoutheners\LaravelDataMapper\Contracts\RouteTransferableObject;
use ReflectionClass;

class ServiceProvider extends BaseServiceProvider
{
    protected static $mappers = [
        Mappers\MapeableObjectMapper::class,
        Mappers\CollectionDataMapper::class,
        Mappers\ModelDataMapper::class,

        Mappers\CarbonDataMapper::class,
        Mappers\BackedEnumDataMapper::class,
        Mappers\GenericObjectDataMapper::class,
        Mappers\ObjectDataMapper::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/data-mapper.php' => config_path('data-mapper.php'),
            ], ['config', 'laravel-data-mapper']);
        }

        $this->app->beforeResolving(
            RouteTransferableObject::class,
            function ($dataClass, $parameters, $app) {
                /** @var \Illuminate\Foundation\Application $app */
                $app->scoped($dataClass, function () use ($dataClass, $app) {
                    $reflector = new ReflectionClass($dataClass);

                    $validateAttributes = $reflector->getAttributes(Validate::class);
                    $validateAttribute = reset($validateAttributes);

                    return map(
                        $app->make($validateAttribute ? $validateAttribute->newInstance()->value : Request::class)
                    )->to($dataClass);
                });
            }
        );
        
        $this->app->instance(PropertyInfoExtractor::class, new PropertyInfoExtractor());
        $this->app->alias(PropertyInfoExtractor::class, 'propertyInfo');
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

    /**
     * Register new dynamic mappers.
     */
    public static function registerMapper(string|array $mapper, bool $replacing = false): void
    {
        $mappers = (array) $mapper;

        static::$mappers = $replacing ? $mappers : array_merge(static::$mappers, $mapper);
    }

    /**
     * Get dynamic mappers.
     *
     * @return array<class-string<DataMapper>>
     */
    public static function getMappers(): array
    {
        return static::$mappers;
        // $mappers = [];

        // foreach (static::$mappers as $mapper) {
        //     $mapperInstance = new $mapper;

        //     if ($mapperInstance instanceof Mappers\DataMapper) {
        //         $mappers[] = $mapperInstance;
        //     }
        // }

        // return $mappers;
    }
}
