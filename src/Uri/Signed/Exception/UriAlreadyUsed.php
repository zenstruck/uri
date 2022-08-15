<?php

namespace Zenstruck\Uri\Signed\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriAlreadyUsed extends VerificationFailed
{
    public const REASON = 'URI has already been used.';
}
