<?php

namespace App\Exceptions;

use RuntimeException;

class HebronPayException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        string $message,
        public readonly int $status = 502,
        public readonly array $body = [],
    ) {
        parent::__construct($message, $status);
    }
}
