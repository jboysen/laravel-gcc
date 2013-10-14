<?php

if (!function_exists('javascript_compiled')) {
    /**
     * Returns a script tag with the compiled file if everything went well,
     * or else it will return script tags with the uncompiled javascript files
     *
     * @param  mixed  $files a string or an array of files
     * @return string Script-tags
     */
    function javascript_compiled($files)
    {
        $gcc = App::make('gccompiler');
        $gcc->setFiles($files);

        if (in_array(App::environment(), \Config::get('laravel-gcc::env')) && $gcc->compile()) {
            return \HTML::script($gcc->getCompiledJsPath());
        }

        $dir = $gcc->getJsDir();

        $output = "";
        foreach ($gcc->getFiles() as $filename => $path) {
            $output .= \HTML::script($dir . '/' . $filename .'?' . \File::lastModified($path));
        }

        return $output;
    }
}
