<?php

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
