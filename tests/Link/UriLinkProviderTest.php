<?php

namespace Zenstruck\Uri\Tests\Link;

use PHPUnit\Framework\TestCase;
use Symfony\Component\WebLink\Link;
use Zenstruck\Uri\Link\UriLink;
use Zenstruck\Uri\Link\UriLinkProvider;
use Zenstruck\Uri\ParsedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @source https://github.com/php-fig/link-util/blob/10e52348a2e9ad4581f2bf3e16458f0861a88c6a/test/GenericLinkProviderTest.php
 */
final class UriLinkProviderTest extends TestCase
{
    /**
     * @test
     */
    public function can_add_links_by_method(): void
    {
        $link = (new UriLink('http://www.google.com'))
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;

        $provider = (new UriLinkProvider())
            ->withLink($link)
        ;

        $this->assertContains($link, $provider->getLinks());
    }

    /**
     * @test
     */
    public function can_add_links_by_constructor(): void
    {
        $link = (new UriLink('http://www.google.com'))
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;

        $provider = (new UriLinkProvider())
            ->withLink($link)
        ;

        $this->assertContains($link, $provider->getLinks());
    }

    /**
     * @test
     */
    public function can_get_links_by_rel(): void
    {
        $link1 = (new UriLink('http://www.google.com'))
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;
        $link2 = (new UriLink('http://www.php-fig.org/'))
            ->withRel('home')
            ->withAttribute('me', 'you')
        ;

        $provider = (new UriLinkProvider())
            ->withLink($link1)
            ->withLink($link2)
        ;

        $links = $provider->getLinksByRel('home');
        $this->assertContains($link2, $links);
        $this->assertFalse(\in_array($link1, $links));
    }

    /**
     * @test
     */
    public function can_remove_links(): void
    {
        $link = (new UriLink('http://www.google.com'))
            ->withRel('next')
            ->withAttribute('me', 'you')
        ;

        $provider = (new UriLinkProvider())
            ->withLink($link)
            ->withoutLink($link)
        ;

        $this->assertFalse(\in_array($link, $provider->getLinks()));
    }

    /**
     * @test
     */
    public function decode(): void
    {
        $links = UriLinkProvider::decode('<https://foo.com>; rel="",<https://bar.com>; rel="next",<https://baz.com>; rel="next prev"; foo="bar",<https://qux.com>; rel="next"');

        $this->assertSame(
            [
                'https://foo.com',
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->toString(), $links->getLinks())
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
            \array_map(fn(UriLink $link) => $link->toString(), \iterator_to_array($links))
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
            \array_map(fn(UriLink $link) => $link->toString(), $links->getLinks())
        );
        $this->assertSame(
            [
                'https://bar.com',
                'https://baz.com',
                'https://qux.com',
            ],
            \array_map(fn(UriLink $link) => $link->toString(), $links->getLinksByRel('next'))
        );
    }

    /**
     * @test
     */
    public function first_for_rel(): void
    {
        $links = $this->sut();

        $this->assertNull($links->firstForRel('invalid'));
        $this->assertSame('https://bar.com', $links->firstForRel('next')->toString());
        $this->assertSame('https://baz.com', $links->firstForRel('prev')->toString());
    }

    /**
     * @test
     */
    public function parse_empty_string(): void
    {
        $this->assertCount(0, UriLinkProvider::decode(''));
        $this->assertCount(0, UriLinkProvider::decode('  '));
        $this->assertCount(0, UriLinkProvider::decode(null));
    }

    private function sut(): UriLinkProvider
    {
        return new UriLinkProvider([
            new UriLink('https://foo.com'),
            new UriLink(new ParsedUri('https://bar.com'), 'next'),
            (new UriLink('https://baz.com', 'next'))->withRel('prev')->withAttribute('foo', 'bar'),
            (new Link('next', 'https://qux.com'))->withAttribute('baz', 1),
        ]);
    }
}
