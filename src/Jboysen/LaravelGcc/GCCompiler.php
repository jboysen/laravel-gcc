<?php

namespace Jboysen\LaravelGcc;

class GCCompiler
{
    /**
     * The default path in the storage folder
     */

    const STORAGE = 'laravel-gcc';

    /**
     * Different modes used
     */
    const MODE_WHITESPACE = 1;
    const MODE_SIMPLE = 2;
    const MODE_ADVANCED = 3;

    /**
     * @var array with absolute path to the actual file as value,
     * and the filename as key
     */
    private $files = array();

    /**
     * @var string complete filename (without '.js')
     */
    private $filename = '';

    /**
     * @var string a prefix used to identify old builds of a new build
     * that needs recompilation
     */
    private $prefix = '';

    /**
     *
     * @var \Illuminate\Config\Repository
     */
    private $config = null;

    /**
     * 
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Creates an array with the absolute path to the actual file as value,
     * and the filename as key
     * 
     * @param array $files a string or an array of files
     */
    public function setFiles($files)
    {
        if (!is_array($files))
            $files = array($files);

        if (count($files) > 0)
        {
            $this->files = array();

            $path = public_path() . '/' . $this->getJsDir();

            foreach ($files as $filename)
            {
                $file = $path . '/' . $filename;
                if (!\File::exists($file))
                    \App::abort(404, "File {$filename} not found");
                $this->files[$filename] = $file;
            }
        }
    }

    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Compiles using Google Closure Compiler
     * 
     * @param array $files string or array of files
     * @return boolean whether successful or not
     */
    public function compile($files = array())
    {
        $this->setFiles($files);

        $this->_calculateFilename();

        if (!\File::exists(static::storagePath($this->filename)))
        {
            $this->_cleanupOldFiles();

            $response = $this->_compile();

            if ($response->hasErrors())
            {
                \Log::error(var_export($response->getErrors(), true));
            }
            
            \File::put(static::storagePath($this->filename), $response->getCompiledCode());
        }
        
        return \File::size(static::storagePath($this->filename)) > 0 ? true : false;
    }

    /**
     * 
     * @return \Closure\CompilerInterface
     */
    private function _compile()
    {
        $compiler = new \Closure\RemoteCompiler();
        $compiler->setMode($this->_getMode());

        foreach ($this->files as $path)
        {
            $compiler->addLocalFile($path);
        }

        $compiler->compile();
        
        return $compiler->getCompilerResponse();
    }

    /**
     * 
     * @return string Translated mode constant
     */
    private function _getMode()
    {
        switch ($this->config->get('laravel-gcc::gcc_mode', 1))
        {
            case static::MODE_WHITESPACE:
            default:
                return \Closure\RemoteCompiler::MODE_WHITESPACE_ONLY;
            case static::MODE_SIMPLE:
                return \Closure\RemoteCompiler::MODE_SIMPLE_OPTIMIZATIONS;
            case static::MODE_ADVANCED:
                return \Closure\RemoteCompiler::MODE_ADVANCED_OPTIMIZATIONS;
        }
    }
    
    /**
     * Removes old builds of the current build
     */
    private function _cleanupOldFiles()
    {
        foreach (\File::glob(static::storagePath() . '/' . $this->prefix . '_*') as $file)
        {
            \File::delete($file);
        }
    }

    public function getCompiledJsPath()
    {
        return \URL::to($this->config->get('laravel-gcc::build_path', 'js-built') . '/' . $this->filename);
    }

    public function getJsDir()
    {
        return $this->config->get('laravel-gcc::public_path', 'js');
    }

    private function _calculateFilename()
    {
        $mtime = 0;

        foreach ($this->files as $path)
        {
            $mtime += \File::lastModified($path);
        }

        $this->prefix = md5(implode('-', array_keys($this->files)));

        $this->filename = $this->prefix . '_' . md5($mtime . implode('-', $this->files)) . '.js';

        return $this->filename;
    }

    /**
     * 
     * @param string $filename Of build file
     * @return \Response Contents if first request, else cache header
     */
    public static function getCompiledFile($filename)
    {
        $path = static::storagePath($filename . '.js');

        if (!\File::exists($path))
            return \App::abort(404);

        $headerMod = \Request::header('If-Modified-Since');

        $headers = array(
            'Content-Type'  => 'text/javascript',
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', \File::lastModified($path))
        );

        $headerGzip = \Request::header('Accept-Encoding');

        if ($headerGzip && str_contains($headerGzip, 'gzip') && function_exists('gzencode'))
        {
            $headers['Content-Encoding'] = 'gzip';
        }

        if ($headerMod && strtotime($headerMod) == \File::lastModified($path))
        {
            return \Response::make(null, 304, $headers);
        }
        else
        {
            $contents = \File::get($path);

            if ($headerGzip && str_contains($headerGzip, 'gzip') && function_exists('gzencode'))
            {
                $contents = gzencode($contents, 9);
            }

            $headers = array_merge($headers, array(
                'Cache-Control'  => 'max-age=' . 60 * 60 * 24 * 7,
                'Expires'        => gmdate('D, d M Y H:i:s \G\M\T', time() + 60 * 60 * 24 * 7),
                'Content-Length' => strlen($contents)
            ));
            return \Response::make($contents, 200, $headers);
        }
    }

    public static function storagePath($path = null)
    {
        $storage = storage_path() . '/' . static::STORAGE;
        return $path ? $storage . '/' . $path : $storage;
    }
    
    /**
     * Remove all compiled bundles
     */
    public static function cleanup()
    {
        foreach (\File::glob(static::storagePath('/*')) as $file)
        {
            \File::delete($file);
        }
    }

}

?>
