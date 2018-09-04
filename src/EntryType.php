<?php

namespace Laravel\Telescope;

class EntryType
{
    public const MAIL = 1;
    public const LOG = 2;
    public const NOTIFICATION = 3;
    public const JOB = 4;
    public const EVENT = 5;
    public const CACHE = 6;
    public const QUERY = 7;
    public const REQUEST = 8;
}
