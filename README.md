# Laravel Scout Settings

Import/Export Algolia settings into your Laravel Scout project.

The easiest way to manage your settings is usually to go to your Algolia dashboard because it has a nice UI and you can test the relevancy directly there.

Once you fine tuned your configuration, you may want to add it to your project. 

This package adds two Laravel commands to your project:

- one to save your settings into a JSON file
- one to push them back to Algolia

This has 3 majors advantages:

1. You can version your configuration with your VCS
2. You can set up a new environement or restore backups easily
3. It lets you customize your settings in JSON format before pushing them

## Install

Install this package with composer

```bash
composer require algolia/laravel-scout-settings
```

#### Laravel 5.5

If you use Laravel 5.5, this package will take advantage of the [Package Auto-Discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) feature.
Nothing more to do to register the commands.

#### Laravel 5.4 and prior

If you use an older version of Laravel, you will have to add the Service Provider to the `providers` array in `config/app.php`

```php
Algolia\Settings\ServiceProvider::class,
```

## Usage

You will now get two new commands available in `artisan`. They both take a model's fully qualified class name, just like Laravel Scout does to import/flush data.

The following example assume you have an `App\Contact` class, which uses the `Searchable` trait.

Note: Scout allows you to customize the index name with the [`searchableAs()`](https://laravel.com/docs/5.4/scout#configuring-model-indexes) method. This package will follow this naming convention.

### Backing up settings (Project ⬅️ Algolia)

The following command will export all the settings from the `App\Contact`'s index into the `resources/settings/prefix_index_name.json` file.

```
php artisan algolia:settings:backup App\Contact
```

### Pushing settings (Project ➡️ Algolia)

The following command will read all the settings from the `resources/settings/prefix_index_name.json` file and import them into Algolia's index.

```
php artisan algolia:settings:push App\Contact
```


## Need help?

Feel free to open a thread on our [Community forum](https://discourse.algolia.com/)
