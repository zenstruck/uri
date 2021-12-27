<?php

namespace Zenstruck\Uri\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Uri;

/**
 * @source https://github.com/guzzle/psr7/blob/7858757f390bbe4b3d81762a97d6e6e786bb70ad/tests/UriTest.php
 */
final class UriTest extends TestCase
{
    /**
     * @test
     */
    public function parses_provided_uri(): void
    {
        $uri = 'https://user:pass@example.com:8080/path/123?q=abc#test';

        $uris = [
            Uri::new($uri),
            Uri::new(new class($uri) {
                private $uri;

                public function __construct($uri)
                {
                    $this->uri = $uri;
                }

                public function __toString(): string
                {
                    return $this->uri;
                }
            }),
        ];

        foreach ($uris as $uri) {
            $this->assertSame('https', (string) $uri->scheme());
            $this->assertSame('user:pass@example.com:8080', (string) $uri->authority());
            $this->assertSame('user:pass', $uri->authority()->userInfo());
            $this->assertSame('user', $uri->user());
            $this->assertSame('pass', $uri->pass());
            $this->assertSame('example.com', (string) $uri->host());
            $this->assertSame(8080, $uri->port());
            $this->assertSame('/path/123', (string) $uri->path());
            $this->assertSame('q=abc', (string) $uri->query());
            $this->assertSame('test', $uri->fragment());
            $this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string) $uri);
        }
    }

    /**
     * @test
     */
    public function can_parse_symfony_request(): void
    {
        $uri = Uri::new(Request::create('https://user:pass@example.com:8080/path/123?q=abc#test'));

        $this->assertSame('https', (string) $uri->scheme());
        $this->assertSame('user:pass@example.com:8080', (string) $uri->authority());
        $this->assertSame('user:pass', $uri->authority()->userInfo());
        $this->assertSame('user', $uri->user());
        $this->assertSame('pass', $uri->pass());
        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame(8080, $uri->port());
        $this->assertSame('/path/123', (string) $uri->path());
        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('', $uri->fragment()); // Symfony Requests do not contain a fragment
        $this->assertSame('https://user:pass@example.com:8080/path/123?q=abc', (string) $uri);
    }

    /**
     * @test
     */
    public function can_parse_symfony_request_without_user_info(): void
    {
        $uri = Uri::new(Request::create('https://example.com:8080/path/123?q=abc#test'));

        $this->assertSame('https', (string) $uri->scheme());
        $this->assertSame('example.com:8080', (string) $uri->authority());
        $this->assertNull($uri->authority()->userInfo());
        $this->assertNull($uri->user());
        $this->assertNull($uri->pass());
        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame(8080, $uri->port());
        $this->assertSame('/path/123', (string) $uri->path());
        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('', $uri->fragment()); // Symfony Requests do not contain a fragment
        $this->assertSame('https://example.com:8080/path/123?q=abc', (string) $uri);
    }

    /**
     * @test
     */
    public function can_transform_and_retrieve_parts_individually(): void
    {
        $uri = Uri::new()
            ->withScheme('https')
            ->withHost('example.com')
            ->withUser('user')
            ->withPass('pass')
            ->withPort(8080)
            ->withPath('/path/123')
            ->withQuery(['q' => 'abc'])
            ->withFragment('test')
        ;

        $this->assertSame('https', (string) $uri->scheme());
        $this->assertSame('user:pass@example.com:8080', (string) $uri->authority());
        $this->assertSame('user:pass', $uri->authority()->userInfo());
        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame(8080, $uri->port());
        $this->assertSame('/path/123', (string) $uri->path());
        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('test', $uri->fragment());
        $this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string) $uri);
    }

    /**
     * @test
     */
    public function can_remove_all_parts(): void
    {
        $uri = Uri::new('https://user:pass@example.com:8080/path/123?q=abc#test')
            ->withoutHost()
            ->withoutPass()
            ->withoutUser()
            ->withoutFragment()
            ->withoutQuery()
            ->withoutPort()
            ->withoutPath()
            ->withoutScheme()
        ;

        $this->assertSame('', (string) $uri);
    }

    /**
     * @test
     */
    public function can_transform_query_with_array(): void
    {
        $uri = Uri::new('http://example.com?foo=bar')
            ->withQuery(['q' => 'abc'])
        ;

        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('http://example.com?q=abc', (string) $uri);
    }

    /**
     * @test
     * @dataProvider getValidUris
     */
    public function valid_uris_stay_valid(string $input): void
    {
        $this->assertSame($input, (string) Uri::new($input));
    }

    public static function getValidUris(): iterable
    {
        return [
            ['urn:path-rootless'],
            //['urn:path:with:colon'], todo
            ['urn:/path-absolute'],
            ['urn:/'],
            // only scheme with empty path
            ['urn:'],
            // only path
            ['/'],
            ['relative/'],
            ['0'],
            // same document reference
            [''],
            // network path without scheme
            ['//example.org'],
            ['//example.org/'],
            //['//example.org?q#h'], // todo
            // only query
            //['?q'], // todo
            ['?q=abc&foo=bar'],
            // only fragment
            ['#fragment'],
            // dot segments are not removed automatically
            ['./foo/../bar'],
            [''],
            ['/'],
            ['var/run/foo.txt'],
            //[':foo'], // todo
            ['/var/run/foo.txt'],
            ['/var/run/foo.txt?foo=bar'],
            ['file:///var/run/foo.txt'],
            ['http://username:password@hostname:9090/path?arg=value#anchor'],
            ['http://username@hostname/path?arg=value#anchor'],
            ['http://hostname/path?arg=value#anchor'],
            ['ftp://username@hostname/path?arg=value#anchor'],
        ];
    }

    /**
     * @test
     */
    public function can_parse_filesystem_path_uri(): void
    {
        $uri = Uri::new('file:///var/run/foo.txt');

        $this->assertSame('/var/run/foo.txt', (string) $uri->path());
        $this->assertSame('file', (string) $uri->scheme());

        $uri = Uri::new('file:/var/run/foo.txt');

        $this->assertSame('/var/run/foo.txt', (string) $uri->path());
        $this->assertSame('file', (string) $uri->scheme());
    }

    /**
     * @test
     * @dataProvider getInvalidUris
     */
    public function invalid_uris_throw_exception(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to parse \"{$input}\".");

        Uri::new($input);
    }

    public function getInvalidUris(): iterable
    {
        return [
            // parse_uri() requires the host component which makes sense for http(s)
            // but not when the scheme is not known or different. So '//' or '///' is
            // currently invalid as well but should not according to RFC 3986.
            ['http://'],
            ['urn://host:with:colon'], // host cannot contain ":"
        ];
    }

    /**
     * @test
     */
    public function port_must_be_valid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port: 100000. Must be between 0 and 65535.');

        Uri::new('//example.com')->withPort(100000);
    }

    /**
     * @test
     */
    public function with_port_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port: -1. Must be between 0 and 65535.');

        Uri::new('//example.com')->withPort(-1);
    }

    /**
     * @test
     */
    public function can_parse_falsey_uri_parts(): void
    {
        $uri = Uri::new('0://0:0@0/0?0#0');

        $this->assertSame('0', (string) $uri->scheme());
        $this->assertSame('0:0@0', (string) $uri->authority());
        $this->assertSame('0:0', $uri->authority()->userInfo());
        $this->assertSame('0', (string) $uri->host());
        $this->assertSame('/0', (string) $uri->path());
        $this->assertSame([0 => ''], $uri->query()->all());
        $this->assertSame('0', $uri->fragment());
        $this->assertSame('0://0:0@0/0?0=#0', (string) $uri);
    }

    /**
     * @test
     */
    public function can_construct_falsey_uri_parts(): void
    {
        $uri = Uri::new()
            ->withScheme('0')
            ->withHost('0')
            ->withUser('0')
            ->withPass('0')
            ->withPath('/0')
            ->withQuery([])
            ->withFragment('0')
        ;

        $this->assertSame('0', (string) $uri->scheme());
        $this->assertSame('0:0@0', (string) $uri->authority());
        $this->assertSame('0:0', $uri->authority()->userInfo());
        $this->assertSame('0', (string) $uri->host());
        $this->assertSame('/0', (string) $uri->path());
        $this->assertSame('', (string) $uri->query());
        $this->assertSame('0', $uri->fragment());
        $this->assertSame('0://0:0@0/0#0', (string) $uri);
    }

    /**
     * @test
     */
    public function scheme_is_normalized_to_lowercase(): void
    {
        $uri = Uri::new('HTTP://example.com');

        $this->assertSame('http', (string) $uri->scheme());
        $this->assertSame('http://example.com', (string) $uri);

        $uri = Uri::new('//example.com')->withScheme('HTTP');

        $this->assertSame('http', (string) $uri->scheme());
        $this->assertSame('http://example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function host_is_normalized_to_lowercase(): void
    {
        $uri = Uri::new('//eXaMpLe.CoM');

        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame('//example.com', (string) $uri);

        $uri = Uri::new()->withHost('eXaMpLe.CoM');

        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame('//example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function port_can_be_removed(): void
    {
        $uri = Uri::new('http://example.com:8080')->withPort(null);

        $this->assertNull($uri->port());
        $this->assertSame('http://example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function immutability(): void
    {
        $uri = Uri::new('http://user@example.com');

        $this->assertNotSame($uri, $uri->withScheme('https'));
        $this->assertNotSame($uri, $uri->withUser('user'));
        $this->assertNotSame($uri, $uri->withPass('pass'));
        $this->assertNotSame($uri, $uri->withHost('example.com'));
        $this->assertNotSame($uri, $uri->withPort(8080));
        $this->assertNotSame($uri, $uri->withPath('/path/123'));
        $this->assertNotSame($uri, $uri->withQuery(['q' => 'abc']));
        $this->assertNotSame($uri, $uri->withFragment('test'));
        $this->assertNotSame($uri, $uri->withoutQueryParams('test'));
        $this->assertNotSame($uri, $uri->withQueryParam('test', 'value'));
    }

    /**
     * @test
     */
    public function manipulating_parts_uriencodes_them(): void
    {
        $uri = Uri::new('https://example.com')
            ->withUser('foo@bar.com')
            ->withPass('pass#word')
            ->withPath('/foo bar/baz')
            ->withQuery(['foo' => 'bar baz'])
        ;

        $this->assertSame('https://foo%40bar.com:pass%23word@example.com/foo%20bar/baz?foo=bar%20baz', $uri->toString());
        $this->assertSame('foo%40bar.com:pass%23word', $uri->authority()->userInfo());
    }

    /**
     * @test
     */
    public function parts_are_uri_decoded(): void
    {
        $uri = Uri::new('http://foo%40bar.com:pass%23word@example.com/foo%20bar/baz?foo=bar%20baz');

        $this->assertSame('foo@bar.com', $uri->user());
        $this->assertSame('pass#word', $uri->pass());
        $this->assertSame('/foo bar/baz', $uri->path()->toString());
        $this->assertSame(['foo' => 'bar baz'], $uri->query()->all());
    }

    /**
     * @test
     */
    public function prefixing_fragment_with_hash_removes_it(): void
    {
        $this->assertSame('fragment', Uri::new()->withFragment('fragment')->fragment());
        $this->assertSame('fragment', Uri::new()->withFragment('#fragment')->fragment());
    }

    /**
     * @test
     */
    public function suffixing_scheme_with_separator_removes_it(): void
    {
        $this->assertSame('http', Uri::new()->withScheme('http')->scheme()->toString());
        $this->assertSame('http', Uri::new()->withScheme('http://')->scheme()->toString());
    }

    /**
     * @test
     */
    public function default_return_values_of_getters(): void
    {
        $uri = Uri::new();

        $this->assertSame('', (string) $uri->scheme());
        $this->assertSame('', (string) $uri->authority());
        $this->assertNull($uri->authority()->userInfo());
        $this->assertSame('', (string) $uri->host());
        $this->assertNull($uri->port());
        $this->assertSame('', (string) $uri->path());
        $this->assertSame('', (string) $uri->query());
        $this->assertSame('', $uri->fragment());
    }

    /**
     * @test
     */
    public function absolute(): void
    {
        $this->assertTrue(Uri::new('https://example.com/foo')->isAbsolute());
        $this->assertFalse(Uri::new('example.com/foo')->isAbsolute());
        $this->assertFalse(Uri::new('/foo')->isAbsolute());
    }

    /**
     * @test
     */
    public function cannot_add_pass_without_user(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot have a password without a username.');

        Uri::new('https://example.com/foo')->withPass('pass');
    }

    /**
     * @test
     */
    public function path_without_host(): void
    {
        $this->assertSame('https://example.com', Uri::new('https://example.com')->toString());
        $this->assertSame('https://example.com/foo', Uri::new('https://example.com')->withPath('foo')->toString());
        $this->assertSame('https://example.com/foo', Uri::new('https://example.com')->withPath('/foo')->toString());
        $this->assertSame('https://example.com/', Uri::new('https://example.com/')->toString());
        $this->assertSame('foo/bar', Uri::new()->withPath('foo/bar')->toString());
        $this->assertSame('/foo/bar', Uri::new()->withPath('/foo/bar')->toString());
        $this->assertSame('//example.com/foo/bar', Uri::new('foo/bar')->withHost('example.com')->toString());
        $this->assertSame('//example.com/foo/bar', Uri::new('/foo/bar')->withHost('example.com')->toString());
    }

    /**
     * @test
     */
    public function can_get_extension(): void
    {
        $this->assertNull(Uri::new('https://example.com/foo')->path()->extension());
        $this->assertSame('txt', Uri::new('https://example.com/foo.txt')->path()->extension());
        $this->assertSame('txt', Uri::new('/foo.txt')->path()->extension());
        $this->assertSame('txt', Uri::new('file:///foo.txt')->path()->extension());
    }

    /**
     * @test
     */
    public function can_get_dirname(): void
    {
        $this->assertSame('/', Uri::new('https://example.com/')->path()->dirname());
        $this->assertSame('/', Uri::new('https://example.com')->path()->dirname());
        $this->assertSame('/', Uri::new('https://example.com/foo.txt')->path()->dirname());
        $this->assertSame('/', Uri::new('/foo.txt')->path()->dirname());
        $this->assertSame('/', Uri::new('file:///foo.txt')->path()->dirname());
        $this->assertSame('/foo/bar', Uri::new('https://example.com/foo/bar/baz.txt')->path()->dirname());
        $this->assertSame('/foo/bar', Uri::new('https://example.com/foo/bar/baz')->path()->dirname());
        $this->assertSame('/foo/bar', Uri::new('https://example.com/foo/bar/baz/')->path()->dirname());
    }

    /**
     * @test
     */
    public function can_get_filename(): void
    {
        $this->assertNull(Uri::new('https://example.com/')->path()->filename());
        $this->assertNull(Uri::new('https://example.com')->path()->filename());
        $this->assertSame('foo', Uri::new('https://example.com/foo.txt')->path()->filename());
        $this->assertSame('foo', Uri::new('/foo.txt')->path()->filename());
        $this->assertSame('foo', Uri::new('file:///foo.txt')->path()->filename());
        $this->assertSame('baz', Uri::new('https://example.com/foo/bar/baz.txt')->path()->filename());
        $this->assertSame('baz', Uri::new('https://example.com/foo/bar/baz')->path()->filename());
        $this->assertSame('baz', Uri::new('https://example.com/foo/bar/baz/')->path()->filename());
    }

    /**
     * @test
     */
    public function can_get_basename(): void
    {
        $this->assertNull(Uri::new('https://example.com/')->path()->basename());
        $this->assertNull(Uri::new('https://example.com')->path()->basename());
        $this->assertSame('foo.txt', Uri::new('https://example.com/foo.txt')->path()->basename());
        $this->assertSame('foo.txt', Uri::new('/foo.txt')->path()->basename());
        $this->assertSame('foo.txt', Uri::new('file:///foo.txt')->path()->basename());
        $this->assertSame('baz.txt', Uri::new('https://example.com/foo/bar/baz.txt')->path()->basename());
        $this->assertSame('baz', Uri::new('https://example.com/foo/bar/baz')->path()->basename());
        $this->assertSame('baz', Uri::new('https://example.com/foo/bar/baz/')->path()->basename());
    }

    /**
     * @test
     */
    public function can_get_the_host_tld(): void
    {
        $this->assertNull(Uri::new('/foo')->host()->tld());
        $this->assertNull(Uri::new('http://localhost/foo')->host()->tld());
        $this->assertSame('com', Uri::new('https://example.com/foo')->host()->tld());
        $this->assertSame('com', Uri::new('https://sub1.sub2.example.com/foo')->host()->tld());
    }

    /**
     * @test
     */
    public function can_trim_path(): void
    {
        $this->assertSame('', Uri::new('http://localhost')->path()->trim());
        $this->assertSame('foo/bar', Uri::new('http://localhost/foo/bar')->path()->trim());
        $this->assertSame('foo/bar', Uri::new('http://localhost/foo/bar/')->path()->trim());
        $this->assertSame('', Uri::new('http://localhost')->path()->ltrim());
        $this->assertSame('foo/bar', Uri::new('http://localhost/foo/bar')->path()->ltrim());
        $this->assertSame('foo/bar/', Uri::new('http://localhost/foo/bar/')->path()->ltrim());
        $this->assertSame('', Uri::new('http://localhost')->path()->rtrim());
        $this->assertSame('/foo/bar', Uri::new('http://localhost/foo/bar')->path()->rtrim());
        $this->assertSame('/foo/bar', Uri::new('http://localhost/foo/bar/')->path()->rtrim());
    }

    /**
     * @test
     * @dataProvider validAbsolutePaths
     */
    public function can_get_absolute_path($uri, $expected): void
    {
        $this->assertSame($expected, Uri::new($uri)->path()->absolute());
    }

    public static function validAbsolutePaths(): iterable
    {
        yield ['http://localhost', '/'];
        yield ['http://localhost/', '/'];
        yield ['http://localhost//', '/'];
        yield ['http://localhost/./', '/'];
        yield ['http://localhost/foo/bar', '/foo/bar'];
        yield ['http://localhost/foo/bar/', '/foo/bar/'];
        yield ['foo/bar', '/foo/bar'];
        yield ['/foo/bar', '/foo/bar'];
        yield ['foo/bar/../baz', '/foo/baz'];
        yield ['/foo/bar/../baz', '/foo/baz'];
        yield ['/foo/bar/../baz/../qux', '/foo/qux'];
        yield ['foo/bar/../..', '/'];
        yield ['foo/bar/../../', '/'];
        yield ['foo/bar/.//baz/.././..', '/foo'];
        yield ['foo/bar/.//baz/.././../', '/foo/'];
    }

    /**
     * @test
     * @dataProvider invalidAbsolutePaths
     */
    public function cannot_get_absolute_path_outside_root($uri): void
    {
        $this->expectException(\RuntimeException::class);

        Uri::new($uri)->path()->absolute();
    }

    public static function invalidAbsolutePaths(): iterable
    {
        yield ['http://localhost/..'];
        yield ['http://localhost/../'];
        yield ['http://localhost/foo/bar/../../..'];
        yield ['http://localhost/foo/bar/../../../'];
    }

    /**
     * @test
     */
    public function can_get_path_segments(): void
    {
        $this->assertSame([], Uri::new('http://localhost')->path()->segments());
        $this->assertSame([], Uri::new('http://localhost/')->path()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('http://localhost/foo/bar')->path()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('http://localhost/foo/bar/')->path()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('/foo/bar/')->path()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('foo/bar')->path()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('foo/bar/')->path()->segments());

        $this->assertNull(Uri::new('http://localhost')->path()->segment(0));
        $this->assertSame('default', Uri::new('http://localhost')->path()->segment(0, 'default'));
        $this->assertNull(Uri::new('http://localhost')->path()->segment(2));
        $this->assertNull(Uri::new('http://localhost/')->path()->segment(1));
        $this->assertSame('foo', Uri::new('http://localhost/foo/bar')->path()->segment(0));
        $this->assertSame('bar', Uri::new('http://localhost/foo/bar')->path()->segment(1));
        $this->assertNull(Uri::new('http://localhost/foo/bar')->path()->segment(2));
    }

    /**
     * @test
     */
    public function can_append_path(): void
    {
        $this->assertSame('http://localhost', Uri::new('http://localhost')->appendPath('')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost/')->appendPath('')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost')->appendPath('/')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost/')->appendPath('/')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost')->appendPath('/')->toString());
        $this->assertSame('http://localhost/foo', Uri::new('http://localhost')->appendPath('foo')->toString());
        $this->assertSame('http://localhost/foo', Uri::new('http://localhost')->appendPath('/foo')->toString());
        $this->assertSame('http://localhost/foo/bar', Uri::new('http://localhost')->appendPath('/foo/bar')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', Uri::new('http://localhost/foo')->appendPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', Uri::new('http://localhost/foo/')->appendPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', Uri::new('http://localhost/foo')->appendPath('bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', Uri::new('http://localhost/foo/')->appendPath('bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', Uri::new('http://localhost/foo')->appendPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', Uri::new('http://localhost/foo/')->appendPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', Uri::new('http://localhost/foo')->appendPath('bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', Uri::new('http://localhost/foo/')->appendPath('bar/baz/')->toString());
        $this->assertSame('foo', Uri::new()->appendPath('foo')->toString());
        $this->assertSame('/foo', Uri::new()->appendPath('/foo')->toString());
        $this->assertSame('foo/bar', Uri::new('foo')->appendPath('bar')->toString());
        $this->assertSame('foo/bar', Uri::new('foo')->appendPath('/bar')->toString());
        $this->assertSame('/foo/bar', Uri::new('/foo')->appendPath('bar')->toString());
        $this->assertSame('/foo/bar', Uri::new('/foo')->appendPath('/bar')->toString());
    }

    /**
     * @test
     */
    public function can_prepend_path(): void
    {
        $this->assertSame('http://localhost', Uri::new('http://localhost')->prependPath('')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost')->prependPath('/')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost/')->prependPath('/')->toString());
        $this->assertSame('http://localhost/', Uri::new('http://localhost')->prependPath('/')->toString());
        $this->assertSame('http://localhost/foo', Uri::new('http://localhost')->prependPath('foo')->toString());
        $this->assertSame('http://localhost/foo', Uri::new('http://localhost')->prependPath('/foo')->toString());
        $this->assertSame('http://localhost/foo/bar', Uri::new('http://localhost')->prependPath('/foo/bar')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', Uri::new('http://localhost/foo')->prependPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', Uri::new('http://localhost/foo/')->prependPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', Uri::new('http://localhost/foo')->prependPath('bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', Uri::new('http://localhost/foo/')->prependPath('bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', Uri::new('http://localhost/foo')->prependPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', Uri::new('http://localhost/foo/')->prependPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', Uri::new('http://localhost/foo')->prependPath('bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', Uri::new('http://localhost/foo/')->prependPath('bar/baz/')->toString());
        $this->assertSame('foo', Uri::new()->prependPath('foo')->toString());
        $this->assertSame('/foo', Uri::new()->prependPath('/foo')->toString());
        $this->assertSame('bar/foo', Uri::new('foo')->prependPath('bar')->toString());
        $this->assertSame('/bar/foo', Uri::new('foo')->prependPath('/bar')->toString());
        $this->assertSame('/bar/foo', Uri::new('/foo')->prependPath('/bar')->toString());
        $this->assertSame('/bar/foo', Uri::new('/foo')->prependPath('bar')->toString()); // absolute path must remain absolute
    }

    /**
     * @test
     */
    public function can_get_scheme_segments(): void
    {
        $this->assertSame([], Uri::new('/foo')->scheme()->segments());
        $this->assertSame(['http'], Uri::new('http://localhost/foo/bar/')->scheme()->segments());
        $this->assertSame(['foo', 'bar'], Uri::new('foo+bar://localhost/foo/bar')->scheme()->segments());

        $this->assertNull(Uri::new('/foo')->scheme()->segment(0));
        $this->assertSame('default', Uri::new('/foo')->scheme()->segment(0, 'default'));
        $this->assertNull(Uri::new('/foo')->scheme()->segment(2));
        $this->assertSame('foo', Uri::new('foo://localhost')->scheme()->segment(0));
        $this->assertSame('foo', Uri::new('foo+bar://localhost')->scheme()->segment(0));
        $this->assertSame('bar', Uri::new('foo+bar://localhost')->scheme()->segment(1));
        $this->assertNull(Uri::new('foo+bar://localhost')->scheme()->segment(2));
    }

    /**
     * @test
     */
    public function scheme_equals(): void
    {
        $this->assertFalse(Uri::new('/foo')->scheme()->equals('http'));
        $this->assertTrue(Uri::new('/foo')->scheme()->equals(''));
        $this->assertTrue(Uri::new('http://localhost/foo')->scheme()->equals('http'));
        $this->assertFalse(Uri::new('http://localhost/foo')->scheme()->equals('https'));
    }

    /**
     * @test
     */
    public function scheme_in(): void
    {
        $this->assertFalse(Uri::new('/foo')->scheme()->in(['http', 'https']));
        $this->assertTrue(Uri::new('/foo')->scheme()->in(['http', 'https', '']));
        $this->assertTrue(Uri::new('http://localhost/foo')->scheme()->in(['http', 'https']));
        $this->assertFalse(Uri::new('ftp://localhost/foo')->scheme()->in(['http', 'https']));
    }

    /**
     * @test
     */
    public function scheme_contains(): void
    {
        $this->assertFalse(Uri::new('/foo')->scheme()->contains('ftp'));
        $this->assertTrue(Uri::new('foo+bar://localhost/foo')->scheme()->contains('foo'));
        $this->assertTrue(Uri::new('foo+bar://localhost/foo')->scheme()->contains('bar'));
        $this->assertFalse(Uri::new('foo+bar://localhost/foo')->scheme()->contains('ftp'));
    }

    /**
     * @test
     */
    public function can_get_host_segments(): void
    {
        $this->assertSame([], Uri::new('/foo')->host()->segments());
        $this->assertSame(['localhost'], Uri::new('http://localhost/foo/bar/')->host()->segments());
        $this->assertSame(['local', 'host'], Uri::new('http://local.host/foo/bar')->host()->segments());

        $this->assertNull(Uri::new('/foo')->host()->segment(0));
        $this->assertSame('default', Uri::new('/foo')->host()->segment(1, 'default'));
        $this->assertNull(Uri::new('/foo')->host()->segment(1));
        $this->assertSame('localhost', Uri::new('http://localhost')->host()->segment(0));
        $this->assertSame('local', Uri::new('http://local.host')->host()->segment(0));
        $this->assertSame('host', Uri::new('http://local.host')->host()->segment(1));
        $this->assertNull(Uri::new('http://local.host')->host()->segment(2));
    }

    /**
     * @test
     */
    public function can_read_query_array(): void
    {
        $uri = Uri::new('/');

        $this->assertSame([], $uri->query()->all());
        $this->assertNull($uri->query()->get('foo'));
        $this->assertSame('default', $uri->query()->get('foo', 'default'));
        $this->assertFalse($uri->query()->has('foo'));

        $uri = Uri::new('/?a=b&c=d');

        $this->assertSame(['a' => 'b', 'c' => 'd'], $uri->query()->all());
        $this->assertSame('d', $uri->query()->get('c'));
        $this->assertTrue($uri->query()->has('a'));
    }

    /**
     * @test
     */
    public function can_manipulate_query_params(): void
    {
        $uri = Uri::new('/?a=b&c=d&e=f');

        $this->assertSame('/?c=d&e=f', (string) $uri->withoutQueryParams('a'));
        $this->assertSame('/?c=d', (string) $uri->withoutQueryParams('a', 'e'));

        $this->assertSame('/', (string) $uri->withOnlyQueryParams('z'));
        $this->assertSame('/?a=b&e=f', (string) $uri->withOnlyQueryParams('a', 'e', 'z'));

        $this->assertSame('/?a=foo&c=d&e=f', (string) $uri->withQueryParam('a', 'foo'));
        $this->assertSame('/?a=b&c=d&e=f&foo=bar', (string) $uri->withQueryParam('foo', 'bar'));
        $this->assertSame(['a' => 'b', 'c' => 'd', 'e' => 'f', 'foo' => [1, 2]], $uri->withQueryParam('foo', [1, 2])->query()->all());
        $this->assertSame('/?a=b&c=d&e=f&foo%5B0%5D=1&foo%5B1%5D=2', (string) $uri->withQueryParam('foo', [1, 2]));
        $this->assertSame('/?a=b&c=d&e=f&foo%5Bg%5D=h', (string) $uri->withQueryParam('foo', ['g' => 'h']));
        $this->assertSame(['a' => 'b', 'c' => 'd', 'e' => 'f', 'foo' => ['g' => 'h']], $uri->withQueryParam('foo', ['g' => 'h'])->query()->all());
    }

    /**
     * @test
     */
    public function can_get_query_param_as_boolean(): void
    {
        $this->assertTrue(Uri::new('/?foo=true')->query()->getBool('foo'));
        $this->assertTrue(Uri::new('/?foo=1')->query()->getBool('foo'));
        $this->assertFalse(Uri::new('/?foo=false')->query()->getBool('foo'));
        $this->assertFalse(Uri::new('/?foo=0')->query()->getBool('foo'));
        $this->assertFalse(Uri::new('/?foo=something')->query()->getBool('foo'));
        $this->assertTrue(Uri::new('/')->query()->getBool('foo', true));
        $this->assertFalse(Uri::new('/')->query()->getBool('foo', false));
        $this->assertFalse(Uri::new('/?foo[]=bar')->query()->getBool('foo', true));
    }

    /**
     * @test
     */
    public function can_get_query_param_as_int(): void
    {
        $this->assertSame(5, Uri::new('/?foo=5')->query()->getInt('foo'));
        $this->assertSame(0, Uri::new('/?foo=something')->query()->getInt('foo'));
        $this->assertSame(0, Uri::new('/')->query()->getInt('foo'));
        $this->assertSame(1, Uri::new('/?foo[]=bar')->query()->getInt('foo'));
        $this->assertSame(3, Uri::new('/')->query()->getInt('foo', 3));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        Uri::new('/?foo=5')->query()->get('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_bool_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        Uri::new('/?foo=5')->query()->getBool('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_int_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        Uri::new('/?foo=5')->query()->getInt('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     * @dataProvider uriComponentsEncodingProvider
     */
    public function uri_components_are_properly_encoded($input, $expectedPath, $expectedQ, $expectedUser, $expectedPass, $expectedFragment, $expectedString): void
    {
        $uri = Uri::new($input);

        $this->assertSame($expectedPath, $uri->path()->toString());
        $this->assertSame($expectedPath, (string) $uri->path());
        $this->assertSame($expectedQ, $uri->query()->get('q'));
        $this->assertSame($expectedUser, $uri->user());
        $this->assertSame($expectedPass, $uri->pass());
        $this->assertSame($expectedFragment, $uri->fragment());
        $this->assertSame($expectedString, (string) $uri);
    }

    public static function uriComponentsEncodingProvider(): iterable
    {
        // todo nested query and encoded query keys
        yield 'Percent encode spaces' => ['http://k b:p d@host/pa th/s b?q=va lue#frag ment', '/pa th/s b', 'va lue', 'k b', 'p d', 'frag ment', 'http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment'];
        yield 'Already encoded' => ['http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment', '/pa th/s b', 'va lue', 'k b', 'p d', 'frag ment', 'http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment'];
        yield 'Path segments not encoded' => ['/pa/th//two?q=va/lue#frag/ment', '/pa/th//two', 'va/lue', null, null, 'frag/ment', '/pa/th//two?q=va%2Flue#frag%2Fment'];
    }
}
