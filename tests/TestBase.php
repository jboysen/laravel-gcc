<?php

abstract class TestBase extends Orchestra\Testbench\TestCase
{

    protected function getPackageProviders()
    {
        return array(
            'Jboysen\LaravelGcc\LaravelGccServiceProvider',
        );
    }

    protected function getEnvironmentSetUp($app)
    {
        
    }

    protected function getApplicationPaths()
    {
        $paths = parent::getApplicationPaths();

        $paths['public'] = __DIR__ . '/fixture/public';

        return $paths;
    }

    protected function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    protected function _mockRemoteCompiler()
    {
        $this->app->instance('gcc.compiler', Mockery::mock('\Closure\RemoteCompiler')
                        ->shouldReceive('setMode')->once()
                        ->shouldReceive('addLocalFile')->once()
                        ->shouldReceive('compile')->once()
                        ->shouldReceive('getCompilerResponse')->once()->andReturn(Mockery::mock()
                                ->shouldReceive('hasErrors')->once()->andReturn(false)
                                ->shouldReceive('getCompiledCode')->once()->andReturn('var x=1;')
                                ->mock())
                        ->mock());
    }

}

?>
