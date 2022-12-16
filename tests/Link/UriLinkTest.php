<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Tests\Link;

use Zenstruck\Uri;
use Zenstruck\Uri\Link\UriLink;
use Zenstruck\Uri\Tests\UriTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @source https://github.com/php-fig/link-util/blob/10e52348a2e9ad4581f2bf3e16458f0861a88c6a/test/LinkTest.php
 */
final class UriLinkTest extends UriTest
{
    /**
     * @test
     */
    public function invalid_decode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UriLink::decode('foo');
    }

    /**
     * @test
     */
    public function wrap_string(): void
    {
        $this->assertSame('/some/endpoint', UriLink::wrap('/some/endpoint')->getHref());
    }

    /**
     * @test
     */
    public function can_set_and_retrieve_values(): void
    {
        $link = (new UriLink('http://example.com'))
            ->withHref('http://www.google.com')
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;

        $this->assertEquals('http://www.google.com', $link->getHref());
        $this->assertContains('next', $link->getRels());
        $this->assertArrayHasKey('me', $link->getAttributes());
        $this->assertEquals('you', $link->getAttributes()['me']);
    }

    /**
     * @test
     */
    public function can_remove_values(): void
    {
        $link = (new UriLink('http://example.com'))
            ->withHref('http://www.google.com')
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;

        $link = $link->withoutAttribute('me')
            ->withoutRel('next')
        ;

        $this->assertEquals('http://www.google.com', $link->getHref());
        $this->assertFalse(\in_array('next', $link->getRels()));
        $this->assertArrayNotHasKey('me', $link->getAttributes());
    }

    /**
     * @test
     */
    public function multiple_rels(): void
    {
        $link = (new UriLink('https://example.com'))
            ->withHref('http://www.google.com')
            ->withRel('next')
            ->withRel('reference')
        ;

        $this->assertCount(2, $link->getRels());
        $this->assertContains('next', $link->getRels());
        $this->assertContains('reference', $link->getRels());
    }

    /**
     * @test
     */
    public function constructor(): void
    {
        $link = new UriLink('http://www.google.com', 'next');

        $this->assertEquals('http://www.google.com', $link->getHref());
        $this->assertContains('next', $link->getRels());
    }

    /**
     * @test
     */
    public function can_check_if_templated(): void
    {
        $this->assertFalse((new UriLink('/some/endpoint'))->isTemplated());
        $this->assertTrue((new UriLink('/some/{endpoint}'))->isTemplated());
    }

    protected function uriFor(string $value): Uri
    {
        return new UriLink($value);
    }
}
