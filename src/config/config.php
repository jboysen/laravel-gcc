<?php

return array(
    
    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | env:  Enable in the environments listed
    |
    */
    
    'env' => array(
        'production',
    ),

	/*
    |--------------------------------------------------------------------------
    | Javascript path and build path
    |--------------------------------------------------------------------------
    |
    | public_path:  Path to javascript files (relatively to /public)   
    |
    | build_path:   Path to use for routing to built files 
    |               (e.g., http://domain.com/{build_path}/{filename}.js)
    |
    */

    'public_path' => 'js',
    'build_path' => 'js-built',
    
    /*
    |--------------------------------------------------------------------------
    | Google Closure Compiler settings
    |--------------------------------------------------------------------------
    |
    | gcc_mode: 1: Whitespace only
    |           2: Simple optimizations
    |           3: Advanced optimizations (risky)
    */
    
    'gcc_mode' => 2,
    
);