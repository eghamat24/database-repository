<?php

namespace Nanvaie\DatabaseRepository;

use Nanvaie\DatabaseRepository\Commands\MakeAll;
use Nanvaie\DatabaseRepository\Commands\MakeEntity;
use Nanvaie\DatabaseRepository\Commands\MakeFactory;
use Nanvaie\DatabaseRepository\Commands\MakeInterfaceRepository;
use Nanvaie\DatabaseRepository\Commands\MakeMySqlRepository;
use Nanvaie\DatabaseRepository\Commands\MakeRedisRepository;
use Nanvaie\DatabaseRepository\Commands\MakeRepository;
use Nanvaie\DatabaseRepository\Commands\MakeResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Foundation\Application as LaravelApplication;

/**
 * Laravel service provider for DatabaseRepository.
 */
class DatabaseRepositoryServiceProvider extends ServiceProvider
{
    private $baseModelStubPath;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->baseModelStubPath = __DIR__ . '/../stubs/Models/PHP'.env('REPOSITORY_PHP_VERSION', '8.0');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->offerPublishing();

        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/repository.php', 'repository');

        $this->registerCommands();
    }

    public function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/repository.php' => $this->app->configPath('repository.php'),
            ], 'repository-config');

            $this->publishes([
                __DIR__ . '/../stubs/PHP'.env('REPOSITORY_PHP_VERSION', '8.0') => $this->app->basePath('stubs'),
            ], 'repository-stubs');

            $this->publishEnums();
            $this->publishEntities();
            $this->publishFactories();
            $this->publishResources();
            $this->publishRepositories();
        }
    }

    private function publishEnums(): void
    {
        $publishPath = $this->app->basePath(config('repository.path.relative.enums', 'app/Models/Enums/'));

        $this->publishes([
            $this->baseModelStubPath . '/Enums/Enum.stub' => $publishPath . 'Enum.php',
        ], ['repository-base-classes', 'repository-base-enum']);

        $this->publishes([
            $this->baseModelStubPath . '/Enums/GriewFilterOperator.stub' => $publishPath . 'GriewFilterOperator.php',
        ], ['repository-base-classes', 'repository-griew-enums']);
    }

    private function publishEntities(): void
    {
        $publishPath = $this->app->basePath(config('repository.path.relative.entities', 'app/Models/Entities/'));

        $this->publishes([
            $this->baseModelStubPath . '/Entity/Entity.stub' => $publishPath . 'Entity.php',
        ], ['repository-base-classes', 'repository-base-entity']);
    }

    private function publishFactories(): void
    {
        $publishPath = $this->app->basePath(config('repository.path.relative.factories', 'app/Models/Factories/'));

        $this->publishes([
            $this->baseModelStubPath . '/Factory/Factory.stub' => $publishPath . 'Factory.php',
        ], ['repository-base-classes', 'repository-base-factory']);

        $this->publishes([
            $this->baseModelStubPath . '/Factory/IFactory.stub' => $publishPath . 'IFactory.php',
        ], ['repository-base-classes', 'repository-base-factory']);
    }

    private function publishResources(): void
    {
        $publishPath = $this->app->basePath(config('repository.path.relative.resources', 'app/Models/Resources/'));

        $this->publishes([
            $this->baseModelStubPath . '/Resource/Resource.stub' => $publishPath . 'Resource.php',
        ], ['repository-base-classes', 'repository-base-resource']);

        $this->publishes([
            $this->baseModelStubPath . '/Resource/IResource.stub' =>  $publishPath . 'IResource.php',
        ], ['repository-base-classes', 'repository-base-resource']);
    }

    private function publishRepositories(): void
    {
        $publishPath = $this->app->basePath(config('repository.path.relative.repositories', 'app/Models/Repositories/'));

        $this->publishes([
            $this->baseModelStubPath . '/Repository/MySqlRepository.stub' => $publishPath . 'MySqlRepository.php',
        ], ['repository-base-classes', 'repository-base-mysql-repository']);

        $this->publishes([
            $this->baseModelStubPath . '/Repository/RedisRepository.stub' =>  $publishPath . 'RedisRepository.php',
        ], ['repository-base-classes', 'repository-base-redis-repository']);
    }

    /**
     * Register custom commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app->singleton('repository.make-all-repository', function () {
                return new MakeAll();
            });

            $this->app->singleton('repository.make-entity', function () {
                return new MakeEntity();
            });

            $this->app->singleton('repository.make-factory', function () {
                return new MakeFactory();
            });

            $this->app->singleton('repository.make-interface-repository', function () {
                return new MakeInterfaceRepository();
            });

            $this->app->singleton('repository.make-mysql-repository', function () {
                return new MakeMySqlRepository();
            });

            $this->app->singleton('repository.make-redis-repository', function () {
                return new MakeRedisRepository();
            });

            $this->app->singleton('repository.make-repository', function () {
                return new MakeRepository();
            });

            $this->app->singleton('repository.make-resource', function () {
                return new MakeResource();
            });

            $this->commands([
                MakeAll::class,
                MakeEntity::class,
                MakeFactory::class,
                MakeInterfaceRepository::class,
                MakeMySqlRepository::class,
                MakeRedisRepository::class,
                MakeRepository::class,
                MakeResource::class
            ]);
        }
    }

}
