# Laravel Scout Settings

Import/Export Algolia settings into your Laravel Scout project

The easiest way to manage your settings is usually to go to your Algolia dashboard because it has a nice UI and you can test the relevancy directly there.

Once you fine tuned your configuration, you may want to add it to your project. This package you add two Laravel commands to save your settings into a JSON file in your project and push it back to Algolia.

This has 3 majors advantages:

1. You can version your configuration with your VCS
2. You can set a new environement or restore backup easily
3. Let you customize your settings in JSON format before pushing them

## Install

Install this package with composer

```bash
composer require algolia/laravel-scout-settings
```

#### Laravel 5.5

If you use Laravel 5.5, this package take advantage of the [Package Auto-Discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) feature so you have nothing more to do.

#### Laravel 5.4 and prior

If you use an older version of Laravel, you will have to add the Service Provider to the `providers` array in `config/app.php`

```php
Algolia\Settings\ServiceProvider::class,
```

## Usage

You will know get two new commands available in `artisan`. They both take a model fully qualified class name, just like Laravel Scout does to import/flush data.

The following example assume you have an `App\Contact` class, which use the `Searchable` trait.

Note: Scout allows you to customize the index name with the [`searchableAs()`](https://laravel.com/docs/5.4/scout#configuring-model-indexes) method. This package will follow this name

### Backing up settings (Project ⬅️ Algolia)

The following command will export all the settings of the `App\Contact`'s index into the `resources/settings/prefix_index_name.json` file.

```
php artisan algolia:settings:backup App\Contact
```

### Pushing settings (Project ➡️ Algolia)

The following command will read all the settings in the `resources/settings/prefix_index_name.json` file and import them into Algolia's index.

```
php artisan algolia:settings:push App\Contact
```


## Need help?

Feel free to open a thread on our [Community forum](https://discourse.algolia.com/)
