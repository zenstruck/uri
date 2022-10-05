<?php

namespace Zenstruck\Uri\Tests\Link;

use PHPUnit\Framework\TestCase;
use Symfony\Component\WebLink\Link;
use Zenstruck\Uri;
use Zenstruck\Uri\Link\UriLink;
use Zenstruck\Uri\Link\UriLinks;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriLinksTest extends TestCase
{
    /**
     * @test
     */
    public function to_string(): void
    {
        $this->assertSame(
            '<https://foo.com>; rel="",<https://bar.com>; rel="next",<https://baz.com>; rel="next prev"; foo="bar",<https://qux.com>; rel="next"; baz="1"',
            (string) $this->sut(),
        );
    }

    /**
     * @test
     */
    public function from_string(): void
    {
        $links = UriLinks::fromString('<https://foo.com>; rel="",<https://bar.com>; rel="next",<https://baz.com>; rel="next prev"; foo="bar",<https://qux.com>; rel="next"');

        $this->assertSame(
            [
                'https://foo.com',
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->uri()->toString(), $links->getLinks())
        );

        $this->assertSame(['next'], $links->getLinks()[1]->getRels());
        $this->assertSame([], $links->getLinks()[1]->getAttributes());
        $this->assertSame(['next', 'prev'], $links->getLinks()[2]->getRels());
        $this->assertSame(['foo' => 'bar'], $links->getLinks()[2]->getAttributes());
    }

    /**
     * @test
     */
    public function countable_and_iterable(): void
    {
        $links = $this->sut();

        $this->assertCount(4, $links);
        $this->assertSame(
            [
                'https://foo.com',
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->uri()->toString(), \iterator_to_array($links))
        );
    }

    /**
     * @test
     */
    public function links_are_normalized(): void
    {
        $links = $this->sut();

        $this->assertSame(
            [
                'https://foo.com',
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->uri()->toString(), $links->getLinks())
        );
        $this->assertSame(
            [
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->uri()->toString(), $links->getLinksByRel('next'))
        );
    }

    /**
     * @test
     */
    public function first_for_rel(): void
    {
        $links = $this->sut();

        $this->assertNull($links->firstForRel('invalid'));
        $this->assertSame('https://bar.com', $links->firstForRel('next')->uri()->toString());
        $this->assertSame('https://baz.com', $links->firstForRel('prev')->uri()->toString());
    }

    /**
     * @test
     */
    public function parse_empty_string(): void
    {
        $this->assertCount(0, UriLinks::fromString(''));
        $this->assertCount(0, UriLinks::fromString('  '));
        $this->assertCount(0, UriLinks::fromString(null));
    }

    private function sut(): UriLinks
    {
        return new UriLinks([
            new UriLink('https://foo.com'),
            new UriLink(new Uri('https://bar.com'), 'next'),
            (new UriLink('https://baz.com', 'next'))->withRel('prev')->withAttribute('foo', 'bar'),
            (new Link('next', 'https://qux.com'))->withAttribute('baz', 1),
        ]);
    }
}
