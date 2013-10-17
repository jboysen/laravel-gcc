<?php

class TestHelper extends TestBase
{

    public function testHelperSingleWithoutCompile()
    {
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->once()
                        ->shouldReceive('setFiles')->with('file1.js')->once()
                        ->shouldReceive('getJsDir')->once()->andReturn('js')
                        ->shouldReceive('getFiles')->once()->andReturn(array(
                            'file1.js' => ''
                        ))
                        ->mock()
        );
        \File::shouldReceive('lastModified')->once()->andReturn(12345);
        $scriptTag = javascript_compiled('file1.js');
        $this->assertEquals("<script src=\"http://localhost/js/file1.js?12345\"></script>\n", $scriptTag);
    }

    public function testHelperMultipleWithoutCompile()
    {
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->once()
                        ->shouldReceive('setFiles')->with(array('file1.js', 'file2.js'))->once()
                        ->shouldReceive('getJsDir')->once()->andReturn('js')
                        ->shouldReceive('getFiles')->once()->andReturn(array(
                            'file1.js' => '',
                            'file2.js' => ''
                        ))
                        ->mock()
        );
        \File::shouldReceive('lastModified')->times(2)->andReturn(12345, 54321);
        $scriptTag = javascript_compiled(array('file1.js', 'file2.js'));
        $this->assertEquals("<script src=\"http://localhost/js/file1.js?12345\"></script>
<script src=\"http://localhost/js/file2.js?54321\"></script>
", $scriptTag);
    }

    public function testHelperSingleWithCompile()
    {
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->once()
                        ->shouldReceive('setFiles')->with('file1.js')->once()
                        ->shouldReceive('compile')->once()->andReturn(true)
                        ->shouldReceive('getCompiledJsURL')->once()->andReturn('http://url/compiled.js')
                        ->mock()
        );
        \Config::set('laravel-gcc::env', array('testing'));
        $scriptTag = javascript_compiled('file1.js');
        $this->assertEquals("<script src=\"http://url/compiled.js\"></script>\n", $scriptTag);
    }

    public function testHelperMultipleWithCompile()
    {
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->once()
                        ->shouldReceive('setFiles')->with(array('file1.js','file2.js'))->once()
                        ->shouldReceive('compile')->once()->andReturn(true)
                        ->shouldReceive('getCompiledJsURL')->once()->andReturn('http://url/compiled.js')
                        ->mock()
        );
        \Config::set('laravel-gcc::env', array('testing'));
        $scriptTag = javascript_compiled(array('file1.js', 'file2.js'));
        $this->assertEquals("<script src=\"http://url/compiled.js\"></script>\n", $scriptTag);
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testHelperWithNonExistingFile()
    {
        javascript_compiled('no-file.js');
    }
    
    public function testHelperSingleWithFailedCompile()
    {
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->once()
                        ->shouldReceive('setFiles')->with('file1.js')->once()
                        ->shouldReceive('compile')->once()->andReturn(false)
                        ->shouldReceive('getJsDir')->once()->andReturn('js')
                        ->shouldReceive('getFiles')->once()->andReturn(array(
                            'file1.js' => ''
                        ))
                        ->mock()
        );
        \Config::set('laravel-gcc::env', array('testing'));
        \File::shouldReceive('lastModified')->once()->andReturn(12345);
        $scriptTag = javascript_compiled('file1.js');
        $this->assertEquals("<script src=\"http://localhost/js/file1.js?12345\"></script>\n", $scriptTag);
    }
    
    public function testReturnDifferentCompiledFiles()
    {
        \Config::set('laravel-gcc::env', array('testing'));
        $scriptTagOne = javascript_compiled('file1.js');
        $scriptTagTwo = javascript_compiled('file2.js');
        $this->assertNotEquals($scriptTagOne, $scriptTagTwo);
    }

}
