<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FeatureTest extends WebTestCase
{
    /**
     * @test
     */
    public function verify_with_route_option(): void
    {
        self::createClient()->request('GET', '/verify-with-route-option');

        $this->assertResponseStatusCodeSame(403);
    }
}
