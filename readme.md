<p align="center"><img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1539108489/telescope-logo.svg"></p>

<p align="center">
<a href="https://packagist.org/packages/laravel/telescope"><img src="https://poser.pugx.org/laravel/telescope/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/telescope"><img src="https://poser.pugx.org/laravel/telescope/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/telescope"><img src="https://poser.pugx.org/laravel/telescope/license.svg" alt="License"></a>
</p>

## Introduction

Laravel Telescope is an elegant debug assistant for the Laravel framework. Telescope provides insight into the requests coming into your application, exceptions, log entries, database queries, queued jobs, mail, notifications, cache operations, scheduled tasks, variable dumps and more. Telescope makes a wonderful companion to your local Laravel development environment.

<p align="center">
<img src="https://res.cloudinary.com/dtfbvvkyp/image/upload/v1539110860/Screen_Shot_2018-10-09_at_1.47.23_PM.png">
</p>

## Installation & Configuration

You may use Composer to install Telescope into your Laravel project:

```sh
 composer require laravel/telescope --dev
```

> **Note:** Telescope requires Laravel 5.7.7+.

After installing Telescope, publish its assets using the `telescope:install` Artisan command. After installing Telescope, you should also run the `migrate` command:

```sh
php artisan telescope:install

php artisan migrate
```

After publishing Telescope's assets, its primary configuration file will be located at `config/telescope.php`. This configuration file allows you to configure your watcher options and each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

#### Updating Telescope

When updating Telescope, you should re-publish Telescope's assets:

```sh
php artisan vendor:publish --tag=telescope-assets --force
```
<a name="dashboard-authorization"></a>
### Dashboard Authorization

Telescope exposes a dashboard at `/telescope`. By default, you will only be able to access this dashboard in the `local` environment. Within your `app/Providers/TelescopeServiceProvider.php` file, there is a `gate` method. This authorization gate controls access to Telescope in **non-local** environments. You are free to modify this gate as needed to restrict access to your Telescope installation:

```php
/**
 * Register the Telescope gate.
 *
 * This gate determines who can access Telescope in non-local environments.
 *
 * @return void
 */
protected function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return in_array($user->email, [
            'taylor@laravel.com',
        ]);
    });
}
```

## License

Laravel Telescope is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
