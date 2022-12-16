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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AlreadyUsed extends VerificationFailed
{
    public const REASON = 'URI has already been used.';
}
