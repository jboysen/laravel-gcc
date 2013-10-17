Laravel4 Google Closure Compiler
===========

This Laravel4 package adds a view-helper `javascript_compiled()` to minimize a single javascript file or bundles of 
several javascript files. Everything is done using [Google Closure Compiler](https://developers.google.com/closure/compiler/).

[![Latest Stable Version](https://poser.pugx.org/jboysen/laravel-gcc/v/stable.png)](https://packagist.org/packages/jboysen/laravel-gcc) 
[![Total Downloads](https://poser.pugx.org/jboysen/laravel-gcc/downloads.png)](https://packagist.org/packages/jboysen/laravel-gcc)
[![Build Status](https://travis-ci.org/jboysen/laravel-gcc.png?branch=master)](https://travis-ci.org/jboysen/laravel-gcc)
[![Coverage Status](https://coveralls.io/repos/jboysen/laravel-gcc/badge.png?branch=master)](https://coveralls.io/r/jboysen/laravel-gcc?branch=master)

## Installation

Install it via Composer by adding the following to your `composer.json`-file (the asterix can be changed to an exact version):

    "jboysen/laravel-gcc": "1.*"

NOTE: *if you get this error: `zendframework/zend-http dev-master requires zendframework/zend-stdlib dev-master -> no matching package found.`, you might have got this [error](https://github.com/composer/composer/issues/2218)..* 

...and add the following to the `providers`-array in your `app/config/app.php`:

    'Jboysen\LaravelGcc\LaravelGccServiceProvider',
    
## Configuration

The config-file is self-explanatory: [`config.php`](https://github.com/jboysen/laravel-gcc/blob/master/src/config/config.php)

To change some of the settings, simply just run (as always) `php artisan config:publish jboysen/laravel-gcc`

## Usage

### View helper

The helper accepts either a string representing a single javascript file or an array representing several files (a bundle). 
A bundle will be compiled in the order given in the array.

Example #1:

    // hello.blade.php
    ...
    {{ javascript_compiled('default.js') }}
    
Example #2:

    // hello.php
    ...
    <?php echo javascript_compiled(array(
      'jquery.js',
      'default.js'
      )); ?>
      
This helper will:

1. Lookup the files given as argument to the helper.
2. Create a unique filename for the compiled files based on the filenames and the last modification time of the files.
3. Compile the bundle (if it's not present).
4. Output a script-tag linking to the compiled file.

### artisan commands

#### `gcc:build`

As compilation of several files sometimes can take time, it is better to do this "offline", that is before any users hit
the web application.

    php artisan gcc:build
    
This command will scan all files in the `/app/views`-directory and find all uses of the view-helper described above, and
compile the bundles immediately, making sure the users won't experience any long response times.
