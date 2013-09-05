<?php

namespace Jboysen\LaravelGcc;

use Illuminate\Console\Command;

class BuildCommand extends Command
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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $bundles = $this->_getBundles();
        
        foreach ($bundles as $bundle)
        {
            $this->comment('Compiling bundle:');
            
            foreach ($bundle as $js)
            {
                $this->line('  ' . $js);
            }
            
            $gcc = \App::make('gccompiler');
            
            if ($gcc->compile($bundle))
            {
                $this->comment('Success...');
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
        
        foreach (\File::allFiles(app_path('views')) as $file)
        {
            echo '.';
            
            foreach ($this->_findMatches($file) as $bundle)
                $bundles[] = $bundle;
        }
        
        echo "\n";
        
        return $bundles;
    }

    private function _findMatches($file)
    {
        $output = array();
        
        $contents = str_replace(' ', '', preg_replace('/\s+/', ' ', \File::get($file)));
        
        if (preg_match_all("/javascript_compiled\\((.*?)\\)/", $contents, $matches))
        {
            foreach ($matches[1] as $bundle)
            {
                $bundle = str_replace('array(', '', $bundle);
                $bundle = str_replace('"', '', $bundle);
                $bundle = str_replace("'", '', $bundle);
                $output[] = explode(',', $bundle);
            }
        }
        
        return $output;
    }

}