# Changelog

## Unreleased
* Introduce `IndexName` value object to guard the difference between remote name 
and local name of an Index
* Sanitize the index names in the files when not using the `--prefix`-option
* Expand tests for further coverage 

## 2.0.0

* Better message in console
* Do not use prefix in file names

    In a typical workflow, you would use a different `scout.prefix` for
    each environment. Generally, you work on dev env, tweak settings in the
    dashboard, save them to your project with `php artisan algolia:settings:backup`
    and push them to your new index with you go to prod env.
    Because in v1 we would link all resources to the full index, if you
    use a different prefix, you weren't able to do this.
    In case you want to keep the behavior like in v1, pass the `--prefix` option
    to the 2 commands.
    
    See [issue #14](https://github.com/algolia/laravel-scout-settings/issues/14)
    
* Introduce new IndexResourceRepository service class
    
    This service allows you to read and write files with your settings,
    synonyms, rules via simple methods. This class doesn't interact with
    Algolia's API but with files in your `resources` folder.
    Thanks @qrazi
    

### 1.0.1

* Move UserAgent definition to ServiceProvider
* Add compatibility with Scout ^4.0 (Thanks to @tomcoonen)

## 1.0.0

* Initial release
