<?php

if (!function_exists('javascript_compiled'))
{
    /**
     * javascript_compiled
     * 
     * @param mixed $args Single javascript file or an array of files.
     *
     * @access public
     */
    function javascript_compiled($files)
    {        
        $gcc = App::make('gccompiler');
        $gcc->setFiles($files);
        
        if (in_array(App::environment(), \Config::get('laravel-closure-compiler::env')))
            if ($gcc->compile())
                return \HTML::script($gcc->getCompiledJsPath());
        
        $dir = $gcc->getJsDir();
        
        $output = "";
        foreach ($gcc->getFiles() as $filename => $path)
        {
            $output .= \HTML::script($dir . '/' . $filename .'?' . \File::lastModified($path));
        }

        return $output;
    }
}
