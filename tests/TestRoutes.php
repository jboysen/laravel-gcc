<?php

use Jboysen\LaravelGcc\GCCompiler;

class TestRoutes extends TestBase
{

    public function testRouteOk()
    {
        GCCompiler::cleanup();
        $this->_mockRemoteCompiler();
        $gcc = new GCCompiler($this->app['config']);
        $gcc->compile('file1.js');
        
        $this->call('GET', $gcc->getCompiledJsURL());
        
        $this->assertResponseOk();
    }
    
    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testRouteNotFound()
    {
        $this->call('GET', '/js-built/compiled.js');
    }
    
    public function testGzipRouteOk()
    {
        GCCompiler::cleanup();
        $this->_mockRemoteCompiler();
        $gcc = new GCCompiler($this->app['config']);
        $gcc->compile('file1.js');
        
        $this->call('GET', $gcc->getCompiledJsURL(), array(), array(), array('HTTP_Accept-Encoding'=>'gzip'));
        
        $this->assertResponseOk();
    }
    
    public function testRouteCache()
    {
        GCCompiler::cleanup();
        $this->_mockRemoteCompiler();
        $gcc = new GCCompiler($this->app['config']);
        $gcc->compile('file1.js');
        
        $time = time();
        
        \File::shouldReceive('exists')->once()->andReturn(true)
                ->shouldReceive('lastModified')->twice()->andReturn($time);
        
        $this->call('GET', $gcc->getCompiledJsURL(), array(), array(), array('HTTP_If-Modified-Since'=>gmdate('D, d M Y H:i:s \G\M\T', $time)));
        
        $this->assertResponseStatus(304);
    }

}