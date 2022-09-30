<?php

namespace Zenstruck\Uri\Tests\Link;

use PHPUnit\Framework\TestCase;
use Zenstruck\Uri;
use Zenstruck\Uri\Link\UriLink;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriLinkTest extends TestCase
{
    /**
     * @test
     */
    public function to_string(): void
    {
        $this->assertSame(
            '<https://baz.com>; rel="next prev"; foo="bar"',
            (string) (new UriLink('https://baz.com', 'next'))->withRel('prev')->withAttribute('foo', 'bar'),
        );
    }

    /**
     * @test
     */
    public function invalid_to_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UriLink::fromString('foo');
    }

    /**
     * @test
     */
    public function with_href_resets_uri(): void
    {
        $link = new UriLink(Uri::new('https://example.com/foo'), 'next');

        $this->assertSame('https://example.com/foo', $link->uri()->toString());
        $this->assertSame('https://example.com/foo', $link->getHref());
        $this->assertSame(['next'], $link->getRels());

        $link = $link->withHref('https://example.com/bar')->withRel('prev');

        $this->assertSame('https://example.com/bar', $link->uri()->toString());
        $this->assertSame('https://example.com/bar', $link->getHref());
        $this->assertSame(['next', 'prev'], $link->getRels());
    }
}
