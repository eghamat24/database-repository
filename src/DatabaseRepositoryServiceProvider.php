<?php

namespace Nanvaie\DatabaseRepository;

use Nanvaie\DatabaseRepository\Commands\MakeAll;
use Nanvaie\DatabaseRepository\Commands\MakeEntity;
use Nanvaie\DatabaseRepository\Commands\MakeEnum;
use Nanvaie\DatabaseRepository\Commands\MakeFactory;
use Nanvaie\DatabaseRepository\Commands\MakeInterfaceRepository;
use Nanvaie\DatabaseRepository\Commands\MakeMySqlRepository;
use Nanvaie\DatabaseRepository\Commands\MakeRedisRepository;
use Nanvaie\DatabaseRepository\Commands\MakeRepository;
use Nanvaie\DatabaseRepository\Commands\MakeResource;
use Nanvaie\DatabaseRepository\Commands\MakeRedis;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Foundation\Application as LaravelApplication;

/**
 * Laravel service provider for DatabaseRepository.
 */
class DatabaseRepositoryServiceProvider extends ServiceProvider
{
    public function __construct($app)
    {
        parent::__construct($app);
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
            ], 'database-repository-config');

        }
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

            $this->app->singleton('repository.make-enum', function () {
                return new MakeEnum();
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
                MakeEnum::class,
                MakeFactory::class,
                MakeInterfaceRepository::class,
                MakeMySqlRepository::class,
                MakeRedisRepository::class,
                MakeRepository::class,
                MakeResource::class,


            ]);
        }
    }

}
