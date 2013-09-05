<?php

namespace Jboysen\LaravelGcc;

/**
 * Description of GCCompiler
 *
 * @author jakob
 */
class GCCompiler
{
    /**
     * The default path in the storage folder
     */
    const STORAGE = 'js-built';
    
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
     * Creates an array with the absolute path to the actual file as value,
     * and the filename as key
     * 
     * @param array $files a string or an array of files
     * @throws Exception if a file is not present in the file system
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
                    throw new Exception ("Not found", 404, "");
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
        
        $this->calculateFilename();
        
        if (!\File::exists(static::storagePath($this->filename)))
        {      
            $this->cleanupOldFiles();
            
            $compiler = new \Closure\RemoteCompiler();
            $compiler->setMode($this->getMode());
            
            foreach ($this->files as $path)
            {
                $compiler->addLocalFile($path);
            }
            
            $compiler->compile();
            $response = $compiler->getCompilerResponse();
            
            if ($response->hasErrors())
            {
                \Log::error(var_export($response->getErrors(), true));
                \File::put(static::storagePath($this->filename), '');
                return false;
            }
            else
            {
                \File::put(static::storagePath($this->filename), $compiler->getCompilerResponse()->getCompiledCode());
                return true;
            }
        }
        else
        {
            return \File::size(static::storagePath($this->filename)) > 0 ? true : false;
        }
    }
    
    /**
     * 
     * @return string Translated mode constant
     */
    private function getMode()
    {
        $mode = \Config::get('laravel-gcc::gcc_mode', 1);
        switch($mode)
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
    private function cleanupOldFiles()
    {
        foreach (\File::glob(static::storagePath() . '/' . $this->prefix . '_*') as $file)
        {
            \File::delete($file);
        }
    }
    
    public function getCompiledJsPath()
    {
        $path = \Config::get('laravel-gcc::build_path', 'js-built');
        return \URL::to($path . '/' . $this->filename . '.js');
    }
    
    public function getJsDir()
    {
        return \Config::get('laravel-gcc::public_path', 'js');
    }
    
    private function calculateFilename()
    {
        $mtime = 0;
        
        foreach ($this->files as $path)
        {
            $mtime += \File::lastModified($path);
        }
        
        $this->prefix = md5(implode('-', array_keys($this->files)));
        
        $this->filename = $this->prefix . '_' . md5($mtime . implode('-', $this->files));
        
        return $this->filename;
    }
    
    /**
     * 
     * @param string $filename Of build file
     * @return \Response Contents if first request, else cache header
     */
    public static function getCompiledFile($filename)
    {
        $path = static::storagePath($filename);
        
        if (!\File::exists($path))
            return \App::abort(404);
        
        $header = \Request::header('If-Modified-Since');
        
        if ($header && strtotime($header) == \File::lastModified($path))
        {
            return \Response::make(null, 304, array(
                'Content-Type' => 'text/javascript',
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', \File::lastModified($path))
            ));
        } 
        else 
        {
            return \Response::make(\File::get($path), 200, array(
                'Content-Type' => 'text/javascript',
                'Last-Modified' => gmdate('D, d M Y H:i:s \G\M\T', \File::lastModified($path)),
                'Cache-Control' => 'max-age=' . 60*60*24*7,
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 60*60*24*7)
            ));
        }
    }
    
    public static function storagePath($filename = null)
    {
        $path = storage_path() . '/' . static::STORAGE;
        return $filename ? $path . '/' . $filename . '.js' : $path;
    }
}

?>
