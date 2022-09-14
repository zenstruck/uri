<?php

namespace Zenstruck\Uri\Signed\Exception;

use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class VerificationFailed extends \RuntimeException
{
    public const REASON = '';

    private Uri $uri;

    /**
     * @internal
     */
    public function __construct(Uri $uri, ?string $message = null, ?\Throwable $previous = null)
    {
        $this->uri = $uri;

        parent::__construct($message ?? static::REASON, 0, $previous);
    }

    final public function uri(): Uri
    {
        return $this->uri;
    }
}
