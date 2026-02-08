<?php

namespace App\Exceptions;

use Exception;

class NewsSourceException extends Exception
{
    public function __construct(
        string $message = 'News source error',
        public readonly ?string $source = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
}
