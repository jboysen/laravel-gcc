Laravel4 Google Closure Compiler
===========

This Laravel4 package adds a view-helper `javascript_compiled()`.

### Installation

Install it via Composer by adding the following to your `composer.json`-file (the asterix can be changed to an exact version):

    "jboysen/laravel-gcc": "1.*"

NOTE: *you might get an error like `zendframework/zend-http dev-master requires zendframework/zend-stdlib dev-master -> no matching package found.`, just ignore that and run composer again...* 

...and add the following to the `providers`-array in your `app/config/app.php`:

    'Jboysen\LaravelGcc\LaravelGccServiceProvider',
    
### Configuration

The config-file is self-explanatory: [`config.php`](https://github.com/jboysen/laravel-gcc/blob/master/src/config/config.php)

To change some of the settings, simply just run (as always) `php artisan config:publish jboysen/laravel-gcc`

### Usage

The helper accepts either a string representing a single javascript file or an array representing several files. 
The files will be compiled in the order given in the array.

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
