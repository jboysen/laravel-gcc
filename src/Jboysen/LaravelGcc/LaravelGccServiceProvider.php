<?php namespace Jboysen\LaravelGcc;

use Illuminate\Support\ServiceProvider;

class LaravelGccServiceProvider extends ServiceProvider {

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
	}
    
    protected function registerGCCompiler()
    {
        $this->app['gccompiler'] = $this->app->share(function() 
        {
            return new GCCompiler;
        });
    }
    
    protected function registerRoutes()
    {
		$this->app->booting(function($app)
		{
            $buildPath = $app['config']->get('laravel-gcc::build_path', 'js-built');
            
            $app['router']->get($buildPath . '/{filename}.js', function($filename)
            {
                return GCCompiler::getCompiledFile($filename);
            });
		});
    }
    
    protected function registerDirectories()
    {
        $storagePath = GCCompiler::storagePath();
        if (!\File::exists($storagePath))
            \File::makeDirectory($storagePath);
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}