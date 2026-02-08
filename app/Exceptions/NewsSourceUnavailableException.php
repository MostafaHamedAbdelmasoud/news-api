<?php

namespace App\Exceptions;

class NewsSourceUnavailableException extends NewsSourceException
{
    public function __construct(
        string $source,
        string $message = 'News source is currently unavailable',
        int $code = 503,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $source, $code, $previous);
    }
}
