<?php

namespace Zenstruck\Uri\Signed\Exception;

use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class VerificationFailed extends \RuntimeException
{
    public const REASON = '';

    private SignedUri $uri;

    final public function __construct(SignedUri $uri, ?string $message = null, ?\Throwable $previous = null)
    {
        $this->uri = $uri;

        parent::__construct($message ?? static::REASON, 0, $previous);
    }

    final public function uri(): SignedUri
    {
        return $this->uri;
    }
}
