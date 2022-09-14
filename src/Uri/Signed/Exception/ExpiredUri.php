<?php

namespace Zenstruck\Uri\Signed\Exception;

use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpiredUri extends VerificationFailed
{
    public const REASON = 'URI has expired.';

    private \DateTimeImmutable $expiredAt;

    /**
     * @internal
     */
    public function __construct(Uri $uri, \DateTimeImmutable $expiredAt, ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct($uri, $message, $previous);

        $this->expiredAt = $expiredAt;
    }

    public function expiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }
}
