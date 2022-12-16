<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Signed\Exception;

use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Expired extends VerificationFailed
{
    public const REASON = 'URI has expired.';

    /**
     * @internal
     */
    public function __construct(Uri $uri, private \DateTimeImmutable $expiredAt, ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct($uri, $message, $previous);
    }

    public function expiredAt(): \DateTimeImmutable
    {
        return $this->expiredAt;
    }
}
