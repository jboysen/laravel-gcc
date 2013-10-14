<?php

namespace Jboysen\LaravelGcc\Commands;

use Illuminate\Console\Command;

class Clean extends Command
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
        $gcc = \App::make('gccompiler');
        $gcc::cleanup();

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
