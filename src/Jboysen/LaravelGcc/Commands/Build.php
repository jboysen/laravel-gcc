<?php

namespace Jboysen\LaravelGcc\Commands;

use Illuminate\Console\Command;

class Build extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gcc:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile all bundles offline';
    
    private $gcc = null;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {        
        $bundles = $this->_getBundles();
        $gcc = \App::make('gcc');

        foreach ($bundles as $bundle) {
            $gcc->reset();
            
            $this->comment('Compiling bundle:');

            foreach ($bundle as $js) {
                $this->line('  ' . $js);
            }

            if ($gcc->compile($bundle)) {
                $this->comment('-> ' . $gcc->getCompiledJsURL());
            }
        }

        $this->comment('All bundles are now compiled.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
        );
    }

    /**
     *
     *
     * @return array
     */
    private function _getBundles()
    {
        $this->comment('Searching files...');

        $bundles = array();

        foreach (\File::allFiles(app_path('views')) as $file) {
            $this->getOutput()->write('.');
                    

            foreach (\Jboysen\LaravelGcc\GCCompiler::findHelperMatches(\File::get($file)) as $bundle) {
                $bundles[] = $bundle;
            }
        }

        $this->getOutput()->writeln('Done!');

        return $bundles;
    }

}
