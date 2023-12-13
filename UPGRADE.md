# Upgrade Guide

With every upgrade, make sure to publish Telescope's assets and clear the view cache:

    php artisan telescope:publish

    php artisan view:clear

## Upgrading To 5.0 From 4.x

### Migration Changes

Telescope 5.0 no longer automatically loads migrations from its own migrations directory. Instead, you should run the following command to publish Telescope's migrations to your application:

```bash
php artisan vendor:publish --tag=telescope-migrations
```

## Upgrading To 4.0 From 3.x

### Minimum PHP Version

PHP 7.3 is now the minimum required version.

### Minimum Laravel Version

Laravel 8.0 is now the minimum required version.
