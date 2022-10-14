<?php

namespace Zenstruck\Uri\Tests;

use Zenstruck\Uri\ParsedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ParsedUriTest extends UriTest
{
    /**
     * @test
     */
    public function can_transform_and_retrieve_parts_individually(): void
    {
        $uri = $this->uriFor('')
            ->withScheme('https')
            ->withHost('example.com')
            ->withUsername('user')
            ->withPassword('pass')
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
        $uri = $this->uriFor('https://user:pass@example.com:8080/path/123?q=abc#test')
            ->withoutScheme()
            ->withoutHost()
            ->withoutPassword()
            ->withoutUsername()
            ->withoutFragment()
            ->withoutQuery()
            ->withoutPort()
            ->withoutPath()
        ;

        $this->assertSame('', (string) $uri);
    }

    /**
     * @test
     */
    public function can_transform_query_with_array(): void
    {
        $uri = $this->uriFor('http://example.com?foo=bar')
            ->withQuery(['q' => 'abc'])
        ;

        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('http://example.com?q=abc', (string) $uri);
    }

    /**
     * @test
     */
    public function port_must_be_valid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port: 100000. Must be between 0 and 65535');

        $this->uriFor('//example.com')->withPort(100000);
    }

    /**
     * @test
     */
    public function with_port_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid port: -1. Must be between 0 and 65535');

        $this->uriFor('//example.com')->withPort(-1);
    }

    /**
     * @test
     */
    public function can_construct_falsey_uri_parts(): void
    {
        $uri = $this->uriFor('')
            ->withScheme('0')
            ->withHost('0')
            ->withUsername('0')
            ->withPassword('0')
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
        $uri = $this->uriFor('//example.com')->withScheme('HTTP');

        $this->assertSame('http', (string) $uri->scheme());
        $this->assertSame('http://example.com', (string) $uri);

        $uri = $this->uriFor('HTTP://example.com')->normalize();

        $this->assertSame('http', (string) $uri->scheme());
        $this->assertSame('http://example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function host_is_normalized_to_lowercase(): void
    {
        $uri = $this->uriFor('')->withHost('eXaMpLe.CoM');

        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame('//example.com', (string) $uri);

        $uri = $this->uriFor('//eXaMpLe.CoM')->normalize();

        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame('//example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function port_can_be_removed(): void
    {
        $uri = $this->uriFor('http://example.com:8080')->withPort(null);

        $this->assertNull($uri->port());
        $this->assertSame('http://example.com', (string) $uri);
    }

    /**
     * @test
     */
    public function immutability(): void
    {
        $uri = $this->uriFor('http://user@example.com');

        $this->assertNotSame($uri, $uri->withScheme('https'));
        $this->assertNotSame($uri, $uri->withUsername('user'));
        $this->assertNotSame($uri, $uri->withPassword('pass'));
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
    public function manipulating_parts_encodes_them(): void
    {
        $uri = $this->uriFor('https://example.com')
            ->withUsername('foo@bar.com')
            ->withPassword('pass#word')
            ->withPath('/foo bar/baz')
            ->withQuery(['foo' => 'bar baz'])
        ;

        $this->assertSame('https://foo%40bar.com:pass%23word@example.com/foo%20bar/baz?foo=bar%20baz', $uri->toString());
        $this->assertSame('foo%40bar.com:pass%23word', $uri->authority()->userInfo());
    }

    /**
     * @test
     */
    public function prefixing_fragment_with_hash_removes_it(): void
    {
        $this->assertSame('fragment', $this->uriFor('')->withFragment('fragment')->fragment());
        $this->assertSame('fragment', $this->uriFor('')->withFragment('#fragment')->fragment());
    }

    /**
     * @test
     */
    public function empty_fragment_converts_to_null(): void
    {
        $this->assertNull($this->uriFor('')->withFragment('')->fragment());
    }

    /**
     * @test
     */
    public function suffixing_scheme_with_separator_removes_it(): void
    {
        $this->assertSame('http', $this->uriFor('')->withScheme('http')->scheme()->toString());
        $this->assertSame('http', $this->uriFor('')->withScheme('http://')->scheme()->toString());
    }

    /**
     * @test
     */
    public function cannot_add_pass_without_user(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot have a password without a username.');

        $this->uriFor('https://example.com/foo')->withPassword('pass');
    }

    /**
     * @test
     */
    public function path_without_host(): void
    {
        $this->assertSame('https://example.com', $this->uriFor('https://example.com')->toString());
        $this->assertSame('https://example.com/foo', $this->uriFor('https://example.com')->withPath('foo')->toString());
        $this->assertSame('https://example.com/foo', $this->uriFor('https://example.com')->withPath('/foo')->toString());
        $this->assertSame('https://example.com/', $this->uriFor('https://example.com/')->toString());
        $this->assertSame('foo/bar', $this->uriFor('')->withPath('foo/bar')->toString());
        $this->assertSame('/foo/bar', $this->uriFor('')->withPath('/foo/bar')->toString());
        $this->assertSame('//example.com/foo/bar', $this->uriFor('foo/bar')->withHost('example.com')->toString());
        $this->assertSame('//example.com/foo/bar', $this->uriFor('/foo/bar')->withHost('example.com')->toString());
    }

    /**
     * @test
     */
    public function can_append_path(): void
    {
        $this->assertSame('http://localhost', $this->uriFor('http://localhost')->appendPath('')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost/')->appendPath('')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost')->appendPath('/')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost/')->appendPath('/')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost')->appendPath('/')->toString());
        $this->assertSame('http://localhost/foo', $this->uriFor('http://localhost')->appendPath('foo')->toString());
        $this->assertSame('http://localhost/foo', $this->uriFor('http://localhost')->appendPath('/foo')->toString());
        $this->assertSame('http://localhost/foo/bar', $this->uriFor('http://localhost')->appendPath('/foo/bar')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', $this->uriFor('http://localhost/foo')->appendPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', $this->uriFor('http://localhost/foo/')->appendPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', $this->uriFor('http://localhost/foo')->appendPath('bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz', $this->uriFor('http://localhost/foo/')->appendPath('bar/baz')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', $this->uriFor('http://localhost/foo')->appendPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', $this->uriFor('http://localhost/foo/')->appendPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', $this->uriFor('http://localhost/foo')->appendPath('bar/baz/')->toString());
        $this->assertSame('http://localhost/foo/bar/baz/', $this->uriFor('http://localhost/foo/')->appendPath('bar/baz/')->toString());
        $this->assertSame('foo', $this->uriFor('')->appendPath('foo')->toString());
        $this->assertSame('/foo', $this->uriFor('')->appendPath('/foo')->toString());
        $this->assertSame('foo/bar', $this->uriFor('foo')->appendPath('bar')->toString());
        $this->assertSame('foo/bar', $this->uriFor('foo')->appendPath('/bar')->toString());
        $this->assertSame('/foo/bar', $this->uriFor('/foo')->appendPath('bar')->toString());
        $this->assertSame('/foo/bar', $this->uriFor('/foo')->appendPath('/bar')->toString());
    }

    /**
     * @test
     */
    public function can_prepend_path(): void
    {
        $this->assertSame('http://localhost', $this->uriFor('http://localhost')->prependPath('')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost')->prependPath('/')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost/')->prependPath('/')->toString());
        $this->assertSame('http://localhost/', $this->uriFor('http://localhost')->prependPath('/')->toString());
        $this->assertSame('http://localhost/foo', $this->uriFor('http://localhost')->prependPath('foo')->toString());
        $this->assertSame('http://localhost/foo', $this->uriFor('http://localhost')->prependPath('/foo')->toString());
        $this->assertSame('http://localhost/foo/bar', $this->uriFor('http://localhost')->prependPath('/foo/bar')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', $this->uriFor('http://localhost/foo')->prependPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', $this->uriFor('http://localhost/foo/')->prependPath('/bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', $this->uriFor('http://localhost/foo')->prependPath('bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', $this->uriFor('http://localhost/foo/')->prependPath('bar/baz')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', $this->uriFor('http://localhost/foo')->prependPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', $this->uriFor('http://localhost/foo/')->prependPath('/bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo', $this->uriFor('http://localhost/foo')->prependPath('bar/baz/')->toString());
        $this->assertSame('http://localhost/bar/baz/foo/', $this->uriFor('http://localhost/foo/')->prependPath('bar/baz/')->toString());
        $this->assertSame('foo', $this->uriFor('')->prependPath('foo')->toString());
        $this->assertSame('/foo', $this->uriFor('')->prependPath('/foo')->toString());
        $this->assertSame('bar/foo', $this->uriFor('foo')->prependPath('bar')->toString());
        $this->assertSame('/bar/foo', $this->uriFor('foo')->prependPath('/bar')->toString());
        $this->assertSame('/bar/foo', $this->uriFor('/foo')->prependPath('/bar')->toString());
        $this->assertSame('/bar/foo', $this->uriFor('/foo')->prependPath('bar')->toString()); // absolute path must remain absolute
    }

    /**
     * @test
     */
    public function can_manipulate_query_params(): void
    {
        $uri = $this->uriFor('/?a=b&c=d&e=f');

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
    public function removing_host_removes_authority(): void
    {
        $this->assertSame('scheme:/path', $this->uriFor('scheme://user:pass@example.com:22/path')->withoutHost()->toString());
    }

    /**
     * @test
     *
     * @dataProvider getValidUris
     */
    public function valid_uris_stay_valid_after_normalize(string $input): void
    {
        $this->assertSame($input, (string) $this->uriFor($input)->normalize());
    }

    /**
     * @test
     *
     * @dataProvider getInvalidUris
     */
    public function invalid_uris_throw_exception_when_normalizing(string $input): void
    {
        $uri = $this->uriFor($input);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unable to parse \"{$input}\".");

        $uri->normalize();
    }

    /**
     * @test
     *
     * @dataProvider getInvalidUris
     */
    public function invalid_uris_stays_invalid_if_only_converted_to_string(string $input): void
    {
        $this->assertSame($input, (string) $this->uriFor($input));
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
     *
     * @dataProvider uriComponentsEncodingProvider
     */
    public function uri_components_are_properly_encoded_when_normalized($input, $expectedPath, $expectedQ, $expectedUser, $expectedPass, $expectedFragment, $expectedString): void
    {
        $uri = $this->uriFor($input);

        $this->assertSame($expectedPath, $uri->path()->toString());
        $this->assertSame($expectedPath, (string) $uri->path());
        $this->assertSame($expectedQ, $uri->query()->get('q'));
        $this->assertSame($expectedUser, $uri->username());
        $this->assertSame($expectedPass, $uri->password());
        $this->assertSame($expectedFragment, $uri->fragment());
        $this->assertSame($expectedString, (string) $uri->normalize());
    }

    public static function uriComponentsEncodingProvider(): iterable
    {
        // todo nested query and encoded query keys
        yield 'Percent encode spaces' => ['http://k b:p d@host/pa th/s b?q=va lue#frag ment', '/pa th/s b', 'va lue', 'k b', 'p d', 'frag ment', 'http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment'];
        yield 'Already encoded' => ['http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment', '/pa th/s b', 'va lue', 'k b', 'p d', 'frag ment', 'http://k%20b:p%20d@host/pa%20th/s%20b?q=va%20lue#frag%20ment'];
        yield 'Path segments not encoded' => ['/pa/th//two?q=va/lue#frag/ment', '/pa/th//two', 'va/lue', null, null, 'frag/ment', '/pa/th//two?q=va%2Flue#frag%2Fment'];
    }

    protected function uriFor(string $value): ParsedUri
    {
        return new ParsedUri($value);
    }
}
