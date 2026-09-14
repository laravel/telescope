<?php

namespace Laravel\Telescope;

class EntryType
{
    public const AI = 'ai';
    public const BATCH = 'batch';
    public const CACHE = 'cache';
    public const COMMAND = 'command';
    public const DUMP = 'dump';
    public const EVENT = 'event';
    public const EXCEPTION = 'exception';
    public const JOB = 'job';
    public const LOG = 'log';
    public const MAIL = 'mail';
    public const MODEL = 'model';
    public const NOTIFICATION = 'notification';
    public const QUERY = 'query';
    public const REDIS = 'redis';
    public const REQUEST = 'request';
    public const SCHEDULED_TASK = 'schedule';
    public const GATE = 'gate';
    public const VIEW = 'view';
    public const CLIENT_REQUEST = 'client_request';

    /**
     * Get all of the entry types.
     *
     * @return string[]
     */
    public static function all()
    {
        return [
            self::BATCH,
            self::CACHE,
            self::CLIENT_REQUEST,
            self::COMMAND,
            self::DUMP,
            self::EVENT,
            self::EXCEPTION,
            self::GATE,
            self::JOB,
            self::LOG,
            self::MAIL,
            self::MODEL,
            self::NOTIFICATION,
            self::QUERY,
            self::REDIS,
            self::REQUEST,
            self::SCHEDULED_TASK,
            self::VIEW,
        ];
    }
}
