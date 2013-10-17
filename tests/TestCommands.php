<?php

class TestCommands extends TestBase
{    
    public function testBuildCommand()
    {
        // mock gccompiler
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('reset')->times(4)
                        ->shouldReceive('compile')->andReturn(true)
                        ->shouldReceive('getCompiledJsURL')->times(4)->andReturn(
                                'http://url/compiled1.js',
                                'http://url/compiled2.js',
                                'http://url/compiled1.js',
                                'http://url/compiled2.js'
                                )
                        ->mock()
        );
        
        // mock all filesystem calls
        \File::shouldReceive('allFiles')->once()->andReturn(array('file.blade.php'))
                ->shouldReceive('get')->once()->andReturn("<html>
    <head>
        {{ javascript_compiled('file1.js') }}
        <?php echo javascript_compiled(array(
            'file1.js',
            'file2.js'
        )); ?>
    </head>
    <body>
        <h1>Header</h1>                        
        <?php echo javascript_compiled(\"file1.js\"); ?>
        {{ javascript_compiled([
            'file2.js',
            'file1.js'
        ]) }}
    </body>
</html>");
        
        // start the actual test
        $tester = new Symfony\Component\Console\Tester\CommandTester(new Jboysen\LaravelGcc\Commands\Build());
        $tester->execute(array());
        
        $output = $tester->getDisplay();
        
        $this->assertEquals("Searching files...
.Done!
Compiling bundle:
  file1.js
-> http://url/compiled1.js
Compiling bundle:
  file1.js
  file2.js
-> http://url/compiled2.js
Compiling bundle:
  file1.js
-> http://url/compiled1.js
Compiling bundle:
  file2.js
  file1.js
-> http://url/compiled2.js
All bundles are now compiled.
", $output);
    }
    
    public function testCleanCommand()
    {   
        // mock the compiler
        \App::instance('gcc', Mockery::mock('GCCompiler')
                        ->shouldReceive('cleanup')->once()
                        ->mock()
        );
        
        // start the actual test
        $testerClean = new Symfony\Component\Console\Tester\CommandTester(new Jboysen\LaravelGcc\Commands\Clean);
        $testerClean->execute(array());
        
        $output = $testerClean->getDisplay();
        
        $this->assertEquals("All compiled bundles are now removed.\n", $output);
    }
    
    
}