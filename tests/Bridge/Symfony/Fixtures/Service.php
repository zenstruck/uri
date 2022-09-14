<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures;

use Zenstruck\Uri\Bridge\Symfony\SignedUriGenerator;
use Zenstruck\Uri\Bridge\Symfony\SignedUriVerifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Service
{
    public SignedUriGenerator $generator;
    public SignedUriVerifier $verifier;

    public function __construct(SignedUriGenerator $generator, SignedUriVerifier $verifier)
    {
        $this->generator = $generator;
        $this->verifier = $verifier;
    }
}
