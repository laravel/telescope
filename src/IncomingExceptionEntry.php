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
        dd('here');
        return resolve(ExceptionHandler::class)->shouldReport($this->exception);
    }
}
