<?php

use Jboysen\LaravelGcc\GCCompiler;

class TestGCCompiler extends TestBase
{

    public function testFindHelperMatches()
    {
        $content = <<<EOT
                <html>
                    <head>
                        {{ javascript_compiled('single.js') }}
                        <?php echo javascript_compiled(array(
                            'file1.js',
                            'file2.js'
                        )); ?>
                    </head>
                    <body>
                        <h1>Header</h1>                        
                        <?php echo javascript_compiled("double.js"); ?>
                        {{ javascript_compiled([
                            'file3.js',
                            'file4.js'
                        ]) }}
                    </body>
                </html>
EOT;

        $bundles = GCCompiler::findHelperMatches($content);

        $this->assertEquals(4, count($bundles));
        $this->assertEquals('single.js', $bundles[0][0]);
        $this->assertEquals('file1.js', $bundles[1][0]);
        $this->assertEquals('file2.js', $bundles[1][1]);
        $this->assertEquals('double.js', $bundles[2][0]);
        $this->assertEquals('file3.js', $bundles[3][0]);
        $this->assertEquals('file4.js', $bundles[3][1]);
    }

    public function testSetFilesSingle()
    {
        $gcc = new GCCompiler($this->app['config']);

        $gcc->setFiles('file1.js');

        $files = $gcc->getFiles();

        $this->assertEquals(1, count($files));
        $this->assertArrayHasKey('file1.js', $files);
        $this->assertEquals(public_path('js') . '/file1.js', $files['file1.js']);
    }

    public function testSetFilesMultiple()
    {
        $gcc = new GCCompiler($this->app['config']);

        $gcc->setFiles(array('file1.js', 'file2.js'));

        $files = $gcc->getFiles();

        $this->assertEquals(2, count($files));
        $this->assertArrayHasKey('file1.js', $files);
        $this->assertArrayHasKey('file2.js', $files);
        $this->assertEquals(public_path('js') . '/file1.js', $files['file1.js']);
        $this->assertEquals(public_path('js') . '/file2.js', $files['file2.js']);
    }

    public function testSetFilesMultipleSame()
    {
        $gcc = new GCCompiler($this->app['config']);

        $gcc->setFiles(array('file1.js', 'file1.js'));

        $files = $gcc->getFiles();

        $this->assertEquals(1, count($files));
        $this->assertArrayHasKey('file1.js', $files);
        $this->assertEquals(public_path('js') . '/file1.js', $files['file1.js']);
    }

    public function testGetCompiledJsURL()
    {
        $gcc = new GCCompiler($this->app['config']);

        $gcc->setFiles('file1.js');

        $this->assertContains('http://localhost/js-built/', $gcc->getCompiledJsURL());

        $this->app['config']->set('laravel-gcc::build_path', 'other-path');
        $this->assertContains('http://localhost/other-path/', $gcc->getCompiledJsURL());
    }

    public function testGetJsDir()
    {
        $gcc = new GCCompiler($this->app['config']);

        $this->assertEquals('js', $gcc->getJsDir());

        $this->app['config']->set('laravel-gcc::public_path', 'other-dir');
        $this->assertEquals('other-dir', $gcc->getJsDir());
    }

    public function testStoragePath()
    {
        $this->assertEquals(storage_path() . DIRECTORY_SEPARATOR . GCCompiler::STORAGE, GCCompiler::storagePath());
    }

    public function testCleanup()
    {
        $this->_mockRemoteCompiler();
        
        $gcc = new GCCompiler($this->app['config']);
        $gcc->compile('file1.js');
        $compiledBefore = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertTrue(0 < count($compiledBefore));

        GCCompiler::cleanup();
        $compiledAfter = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(0, count($compiledAfter));
    }

    public function testCompile()
    {
        GCCompiler::cleanup();

        $this->_mockRemoteCompiler();

        $gcc = new GCCompiler($this->app['config']);
        $result = $gcc->compile('file1.js');
        $this->assertEquals($result, true);

        $compiled = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(1, count($compiled));
    }

    public function testCompileWithError()
    {
        GCCompiler::cleanup();

        \App::instance('gcc.compiler', Mockery::mock('\Closure\RemoteCompiler')
                        ->shouldReceive('setMode')->once()
                        ->shouldReceive('addLocalFile')->once()
                        ->shouldReceive('compile')->once()
                        ->shouldReceive('getCompilerResponse')->once()->andReturn(Mockery::mock('\Closure\CompilerInterface')
                                ->shouldReceive('hasErrors')->once()->andReturn(true)
                                ->shouldReceive('getErrors')->once()->andReturn('error')
                                ->shouldReceive('getCompiledCode')->once()->andReturn('')
                                ->mock())
                        ->mock());

        \Log::shouldReceive('error')->once();

        $gcc = new GCCompiler($this->app['config']);
        $result = $gcc->compile('file1.js');
        $this->assertEquals($result, false);

        $compiled = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(1, count($compiled));
    }

    /**
     * Test that the old compiled file is removed when the org source
     * has changed, i.e. the timestamp has been updated in this case
     */
    public function testCompileWithChange()
    {
        // clean sheet
        GCCompiler::cleanup();

        $this->_mockRemoteCompiler();

        // compile one time
        $gcc = new GCCompiler($this->app['config']);
        $this->assertEquals($gcc->compile('file1.js'), true);

        $first = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(1, count($first));

        touch(public_path('js') . '/file1.js');

        // compile again (file has changed)
        $this->_mockRemoteCompiler();

        $gcc->reset();
        $this->assertEquals($gcc->compile('file1.js'), true);

        $second = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(1, count($second));
        $this->assertNotEquals($first[0], $second[0]);
    }
    
    private function _mockRemoteCompilerMode($mode)
    {
        $this->app->instance('gcc.compiler', Mockery::mock('\Closure\RemoteCompiler')
                        ->shouldReceive('setMode')->with($mode)->once()
                        ->shouldReceive('addLocalFile')->once()
                        ->shouldReceive('compile')->once()
                        ->shouldReceive('getCompilerResponse')->once()->andReturn(Mockery::mock()
                                ->shouldReceive('hasErrors')->once()->andReturn(false)
                                ->shouldReceive('getCompiledCode')->once()->andReturn('var x=1;')
                                ->mock())
                        ->mock());
    }

    public function testGetModeWhitespace()
    {
        GCCompiler::cleanup();        
        $this->app['config']->set('laravel-gcc::gcc_mode', GCCompiler::MODE_WHITESPACE);
        $this->_mockRemoteCompilerMode(\Closure\RemoteCompiler::MODE_WHITESPACE_ONLY);
        $gcc = new GCCompiler($this->app['config']);
        $this->assertTrue($gcc->compile('file1.js'));
    }
    
    public function testGetModeAdvanced()
    {
        GCCompiler::cleanup();        
        $this->app['config']->set('laravel-gcc::gcc_mode', GCCompiler::MODE_ADVANCED);
        $this->_mockRemoteCompilerMode(\Closure\RemoteCompiler::MODE_ADVANCED_OPTIMIZATIONS);
        $gcc = new GCCompiler($this->app['config']);
        $this->assertTrue($gcc->compile('file1.js'));
    }
    
    public function testWithoutMocks()
    {
        GCCompiler::cleanup();
        $first = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(0, count($first));
        
        $gcc = new GCCompiler($this->app['config']);
        $this->assertTrue($gcc->compile('file1.js'));
        $second = \File::glob(GCCompiler::storagePath(DIRECTORY_SEPARATOR . '*'));
        $this->assertEquals(1, count($second));        
    }

}
