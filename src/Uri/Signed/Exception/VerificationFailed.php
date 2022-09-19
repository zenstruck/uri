<?php

namespace Zenstruck\Uri\Signed\Exception;

use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class VerificationFailed extends \RuntimeException
{
    public const REASON = '';

    /**
     * @internal
     */
    public function __construct(private Uri $uri, ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct($message ?? static::REASON, 0, $previous);
    }

    final public function uri(): Uri
    {
        return $this->uri;
    }
}
