<?php

namespace Zenstruck\Uri\Signed\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InvalidSignature extends VerificationFailed
{
    public const REASON = 'Invalid signature.';
}
