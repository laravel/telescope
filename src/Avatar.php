<?php

namespace Laravel\Telescope;

use Closure;
use Illuminate\Support\Str;

class Avatar
{
    /**
     * The callback that should be used to get the Telescope user avatar.
     *
     * @var \Closure
     */
    protected static $callback;

    /**
     * Get an avatar URL for an entry user.
     *
     * @param  array       $user User payload from the EntryResult.
     * @return string|null
     */
    public static function url(array $user)
    {
        if (empty($user['email'])) {
            return;
        }

        switch (config('telescope.avatar_driver', 'gravatar')) {
            case 'custom':
                return static::resolve($user);
            case 'gravatar':
                $hash = md5(Str::lower($user['email']));

                return "https://www.gravatar.com/avatar/{$hash}?s=200";
            default:
                break;
        }
    }

    /**
     * Register the Telescope user avatar callback.
     *
     * @param  \Closure  $callback
     */
    public static function register(Closure $callback)
    {
        static::$callback = $callback;
    }

    /**
     * Find the custom avatar for a user.
     *
     * @param  array       $user
     * @return string|null
     */
    protected static function resolve($user)
    {
        if (static::$callback !== null) {
            return call_user_func(static::$callback, $user['id'], $user['email']);
        }
    }
}
