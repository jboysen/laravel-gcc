<?php

namespace Jboysen\LaravelGcc;

use Illuminate\Support\ServiceProvider;

class LaravelGccServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('jboysen/laravel-gcc');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerGCCompiler();
        $this->registerRoutes();
        $this->registerDirectories();
        $this->registerCommands();
    }

    /**
     * Create an instance of the compiler
     *
     * @return void
     */
    protected function registerGCCompiler()
    {
        $this->app['gccompiler'] = $this->app->share(function ($app) {
                    return new GCCompiler($app['config']);
                });
    }

    /**
     * Register the js-built route based on the config
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->app->booting(function($app) {
                    $buildPath = $app['config']->get('laravel-gcc::build_path', 'js-built');

                    $app['router']->get($buildPath . '/{filename}.js', function ($filename) {
                                return GCCompiler::getCompiledFile($filename);
                            });
                });
    }

    /**
     * Create directories
     *
     * @return void
     */
    protected function registerDirectories()
    {
        $storagePath = GCCompiler::storagePath();
        if (!\File::exists($storagePath)) {
            \File::makeDirectory($storagePath);
            chmod($storagePath, 0757);
        }
    }

    /**
     *
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app['command.gcc.build'] = $this->app->share(function() {
                    return new Commands\Build;
                });
        $this->app['command.gcc.clean'] = $this->app->share(function() {
                    return new Commands\Clean;
                });
        $this->commands(
                'command.gcc.build', 'command.gcc.clean'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'gccompiler',
            'command.gcc.build',
            'command.gcc.clean'
        );
    }

}
