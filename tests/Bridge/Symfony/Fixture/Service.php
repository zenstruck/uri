<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixture;

use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlVerifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Service
{
    public function __construct(public SignedUrlGenerator $generator, public SignedUrlVerifier $verifier)
    {
    }
}
