<?php

namespace Jboysen\LaravelGcc;

class GCCompiler
{
    /**
     * The default path in the storage folder
     */
    const STORAGE = 'laravel-gcc';
    
    /**
     * Default public javascript folder
     */
    const JS_PATH = 'js';
    
    /**
     * Default url js path
     */
    const JS_BUILD_PATH = 'js-built';

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
    private $filename = null;

    /**
     * @var string a prefix used to identify old builds of a new build
     * that needs recompilation
     */
    private $prefix = null;

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
        if (!is_array($files)) {
            $files = array($files);
        }

        if (count($files) > 0) {
            $this->files = array();

            $path = public_path() . DIRECTORY_SEPARATOR . $this->getJsDir();

            foreach ($files as $filename) {
                $file = $path . DIRECTORY_SEPARATOR . $filename;
                if (!\File::exists($file)) 
                    \App::abort(404, "File {$file} not found");
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
     * @param  array   $files string or array of files
     * @return boolean whether successful or not
     */
    public function compile($files = array())
    {
        $this->setFiles($files);

        $this->_calculateFilename();

        if (!\File::exists(static::storagePath($this->filename))) {
            $this->_cleanupOldFiles();

            $response = $this->_compile();

            if ($response->hasErrors()) {
                \Log::error(var_export($response->getErrors(), true));
            }

            \File::put(static::storagePath($this->filename), $response->getCompiledCode());
            chmod(static::storagePath($this->filename), 0757);
        }

        // if there was any errors, the compiled file will be empty
        return \File::size(static::storagePath($this->filename)) > 0;
    }

    /**
     *
     * @return \Closure\CompilerInterface
     */
    private function _compile()
    {
        $compiler = \App::make('gcc.compiler');
        $compiler->setMode($this->_getMode());

        foreach ($this->files as $path) {
            $compiler->addLocalFile($path);
        }

        $compiler->compile();

        return $compiler->getCompilerResponse();
    }

    /**
     *
     * @return string Converted mode constant
     */
    private function _getMode()
    {
        switch ($this->config->get('laravel-gcc::gcc_mode', static::MODE_WHITESPACE)) {
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
        foreach (\File::glob(static::storagePath() . DIRECTORY_SEPARATOR . $this->prefix . '_*') as $file) {
            \File::delete($file);
        }
    }

    /**
     * @return string URL to the filename
     */
    public function getCompiledJsURL()
    {
        $this->_calculateFilename();
        return \URL::to($this->config->get('laravel-gcc::build_path', static::JS_BUILD_PATH) . DIRECTORY_SEPARATOR . $this->filename);
    }

    public function getJsDir()
    {
        return $this->config->get('laravel-gcc::public_path', static::JS_PATH);
    }
    
    public function reset()
    {
        $this->filename = null;
        $this->prefix = null;
        $this->files = array();
    }

    /**
     * Create filename based on the filename and the modification time of the
     * files. This ensures that we only recompile when the files have been 
     * changed.
     * @return string Filename
     */
    private function _calculateFilename()
    {
        if ($this->filename === null) {
            $mtime = 0;

            foreach ($this->files as $path) {
                $mtime += \File::lastModified($path);
            }

            $this->prefix = md5(implode('-', array_keys($this->files)));

            $this->filename = $this->prefix . '_' . md5($mtime . implode('-', $this->files)) . '.js';
        }
        return $this->filename;
    }

    /**
     *
     * @param  string    $filename Of build file
     * @return \Response Contents if first request, else cache header
     */
    public static function getCompiledFile($filename)
    {
        $path = static::storagePath($filename . '.js');

        if (!\File::exists($path)) {
            return \App::abort(404);
        }
        
        $headerMod = \Request::header('If-Modified-Since');

        $headers = array(
            'Content-Type'  => 'text/javascript',
            'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', \File::lastModified($path))
        );

        $headerGzip = \Request::header('Accept-Encoding');
        
        if ($headerGzip && str_contains($headerGzip, 'gzip') && function_exists('gzencode')) {
            $headers['Content-Encoding'] = 'gzip';
        }

        if ($headerMod && strtotime($headerMod) == \File::lastModified($path)) {
            return \Response::make(null, 304, $headers);
        } else {
            $contents = \File::get($path);

            if ($headerGzip && str_contains($headerGzip, 'gzip') && function_exists('gzencode')) {
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
        $storage = storage_path() . DIRECTORY_SEPARATOR . static::STORAGE;

        return $path ? $storage . DIRECTORY_SEPARATOR . $path : $storage;
    }

    /**
     * Remove all compiled bundles
     */
    public static function cleanup()
    {
        foreach (\File::glob(static::storagePath(DIRECTORY_SEPARATOR . '*')) as $file) {
            \File::delete($file);
        }
    }
    
    /**
     * Find all occurences of javascript_compiled
     * @param string $contents Usually content of a file
     * @return array Of parameters to found javascript_compiled()
     */
    public static function findHelperMatches($contents)
    {
        $output = array();

        $contents = str_replace(' ', '', preg_replace('/\s+/', ' ', $contents));
        
        if (preg_match_all("/javascript_compiled\\((.*?)\\)/", $contents, $matches)) {
            foreach ($matches[1] as $bundle) {
                $bundle = str_replace(array('array(', '[', ']', '"', "'"), '', $bundle);
                $output[] = explode(',', $bundle);
            }
        }

        return $output;
    }

}
