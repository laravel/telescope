<?php

namespace Laravel\Telescope;

class EntryType
{
    public const CACHE = 'cache';
    public const COMMAND = 'command';
    public const EVENT = 'event';
    public const EXCEPTION = 'exception';
    public const JOB = 'job';
    public const LOG = 'log';
    public const MAIL = 'mail';
    public const NOTIFICATION = 'notification';
    public const QUERY = 'query';
    public const REQUEST = 'request';
    public const SCHEDULED_COMMAND = 'schedule';
}
