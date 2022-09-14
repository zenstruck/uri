<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Uri\Bridge\Symfony\SignedUriGenerator;
use Zenstruck\Uri\Bridge\Symfony\SignedUriVerifier;
use Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class IntegrationTest extends KernelTestCase
{
    /**
     * @test
     */
    public function can_autowire_generator_and_verifier(): void
    {
        $service = self::getContainer()->get(Service::class);

        $this->assertInstanceOf(SignedUriGenerator::class, $service->generator);
        $this->assertInstanceOf(SignedUriVerifier::class, $service->verifier);
    }
}
