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

/**
 * Laravel service provider for DatabaseRepositor.
 *
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
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/Config/' . self::CONFIG_FILE => config_path(self::CONFIG_FILE),
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerCommand();
    }

    /**
     * Register custom commands.
     */
    private function registerCommand()
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
            'command.make-all-repository',
            'command.make-entity',
            'command.make-factory',
            'command.make-interface-repository',
            'command.make-mysql-repository',
            'command.make-redis-repository',
            'command.make-repository',
            'command.make-resource'
        ]);
    }

}
