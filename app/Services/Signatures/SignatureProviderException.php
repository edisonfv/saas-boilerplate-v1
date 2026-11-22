<?php

namespace App\Services\Signatures;

use RuntimeException;
use Throwable;

/**
 * The provider refused an application or could not be reached. The
 * message is safe to show to the tenant operator.
 */
class SignatureProviderException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(string $message, public readonly array $response = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
