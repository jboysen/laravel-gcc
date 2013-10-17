<?php

class TestServiceProvider extends TestBase
{

    public function testDirectoryCreation()
    {
        \File::deleteDirectory(storage_path(Jboysen\LaravelGcc\GCCompiler::STORAGE));

        $this->assertFalse(file_exists(storage_path(Jboysen\LaravelGcc\GCCompiler::STORAGE)));

        $this->createApplication();

        $this->assertTrue(file_exists(storage_path(Jboysen\LaravelGcc\GCCompiler::STORAGE)));
    }

}
