<?php

namespace App\Exceptions;

use Exception;

class ElasticsearchException extends Exception
{
    public function __construct(
        string $message = 'Elasticsearch error',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
