<?php

namespace Zenstruck\Uri\Signed\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpiredUri extends VerificationFailed
{
    public const REASON = 'URI has expired.';

    public function expiredAt(): \DateTimeImmutable
    {
        $expiredAt = $this->uri()->expiresAt();

        \assert($expiredAt instanceof \DateTimeImmutable);

        return $expiredAt;
    }
}
