<?php

namespace Nanvaie\DatabaseRepository;

use Nanvaie\DatabaseRepository\Commands\MakeAllRepository;
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
    /**
     * The package configuration file.
     */
    const CONFIG_FILE = 'repository.php';

    /**
     * Bootstrap the application events.
     */
//    public function boot()
//    {
//
//
//        $this->register();
//    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/' . self::CONFIG_FILE => config_path(self::CONFIG_FILE),
            ], 'config');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('repository');
        }

        $this->registerCommand();
    }

    /**
     * Register custom commands.
     */
    private function registerCommand(): void
    {
        $this->app->singleton('command.make-all-repository', function () {
            return new MakeAllRepository();
        });

        $this->app->singleton('command.make-entity', function () {
            return new MakeEntity();
        });

        $this->app->singleton('command.make-factory', function () {
            return new MakeFactory();
        });

        $this->app->singleton('command.make-interface-repository', function () {
            return new MakeInterfaceRepository();
        });

        $this->app->singleton('command.make-mysql-repository', function () {
            return new MakeMySqlRepository();
        });

        $this->app->singleton('command.make-redis-repository', function () {
            return new MakeRedisRepository();
        });

        $this->app->singleton('command.make-repository', function () {
            return new MakeRepository();
        });

        $this->app->singleton('command.make-resource', function () {
            return new MakeResource();
        });

        $this->commands([
            MakeAllRepository::class,
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
