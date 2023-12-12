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
