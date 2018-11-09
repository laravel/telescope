<?php

namespace Laravel\Telescope;

use Illuminate\Contracts\Debug\ExceptionHandler;

class IncomingExceptionEntry extends IncomingEntry
{
    /**
     * The underlying exception instance.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new incoming entry instance.
     *
     * @param  \Exception  $exception
     * @param  array  $content
     * @return void
     */
    public function __construct($exception, array $content)
    {
        $this->exception = $exception;

        parent::__construct($content);
    }

    /**
     * Determine if the incoming entry is a reportable exception.
     *
     * @return bool
     */
    public function isReportableException()
    {
        $handler = app(ExceptionHandler::class);

        return method_exists($handler, 'shouldReport')
                ? $handler->shouldReport($this->exception) : true;
    }

    /**
     * Determine if the incoming entry is an exception.
     *
     * @return bool
     */
    public function isException()
    {
        return true;
    }

    /**
     * Calculate the family look-up hash for the incoming entry.
     *
     * @return string
     */
    public function familyHash()
    {
        return md5($this->content['file'].$this->content['line']);
    }
}
