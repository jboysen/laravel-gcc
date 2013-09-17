<?php

namespace Jboysen\LaravelGcc;

use Illuminate\Console\Command;

class CleanCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gcc:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all compiled bundles';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        GCCompiler::cleanup();
        
        $this->comment('All compiled bundles are now removed.');
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
}