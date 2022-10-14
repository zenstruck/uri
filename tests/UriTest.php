<?php

namespace Zenstruck\Uri\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @source https://github.com/guzzle/psr7/blob/7858757f390bbe4b3d81762a97d6e6e786bb70ad/tests/UriTest.php
 */
abstract class UriTest extends TestCase
{
    /**
     * @test
     */
    public function parses_provided_uri(): void
    {
        $uri = $this->uriFor('https://user:pass@example.com:8080/path/123?q=abc#test');

        $this->assertSame('https', (string) $uri->scheme());
        $this->assertSame('user:pass@example.com:8080', (string) $uri->authority());
        $this->assertSame('user:pass', $uri->authority()->userInfo());
        $this->assertSame('user', $uri->username());
        $this->assertSame('pass', $uri->password());
        $this->assertSame('example.com', (string) $uri->host());
        $this->assertSame(8080, $uri->port());
        $this->assertSame('/path/123', (string) $uri->path());
        $this->assertSame('q=abc', (string) $uri->query());
        $this->assertSame('test', $uri->fragment());
        $this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string) $uri);
    }

    /**
     * @test
     *
     * @dataProvider getValidUris
     */
    public function valid_uris_stay_valid(string $input): void
    {
        $this->assertSame($input, (string) $this->uriFor($input));
    }

    public static function getValidUris(): iterable
    {
        return [
            ['urn:path-rootless'],
            // ['urn:path:with:colon'], todo
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
            // ['//example.org?q#h'], // todo
            // only query
            // ['?q'], // todo
            ['?q=abc&foo=bar'],
            // only fragment
            ['#fragment'],
            // dot segments are not removed automatically
            ['./foo/../bar'],
            [''],
            ['/'],
            ['var/run/foo.txt'],
            // [':foo'], // todo
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
        $uri = $this->uriFor('file:///var/run/foo.txt');

        $this->assertSame('/var/run/foo.txt', (string) $uri->path());
        $this->assertSame('file', (string) $uri->scheme());

        $uri = $this->uriFor('file:/var/run/foo.txt');

        $this->assertSame('/var/run/foo.txt', (string) $uri->path());
        $this->assertSame('file', (string) $uri->scheme());
    }

    /**
     * @test
     */
    public function can_parse_falsey_uri_parts(): void
    {
        $uri = $this->uriFor('0://0:0@0/0?0#0');

        $this->assertSame('0', (string) $uri->scheme());
        $this->assertSame('0:0@0', (string) $uri->authority());
        $this->assertSame('0:0', $uri->authority()->userInfo());
        $this->assertSame('0', (string) $uri->host());
        $this->assertSame('/0', (string) $uri->path());
        $this->assertSame([0 => ''], $uri->query()->all());
        $this->assertSame('0', $uri->fragment());
        $this->assertSame('0://0:0@0/0?0#0', (string) $uri);
    }

    /**
     * @test
     */
    public function parts_are_decoded(): void
    {
        $uri = $this->uriFor('http://foo%40bar.com:pass%23word@example.com/foo%20bar/baz?foo=bar%20baz');

        $this->assertSame('foo@bar.com', $uri->username());
        $this->assertSame('pass#word', $uri->password());
        $this->assertSame('/foo bar/baz', $uri->path()->toString());
        $this->assertSame(['foo' => 'bar baz'], $uri->query()->all());
    }

    /**
     * @test
     */
    public function default_return_values_of_getters(): void
    {
        $uri = $this->uriFor('');

        $this->assertSame('', (string) $uri->scheme());
        $this->assertSame('', (string) $uri->authority());
        $this->assertNull($uri->authority()->userInfo());
        $this->assertSame('', (string) $uri->host());
        $this->assertNull($uri->port());
        $this->assertSame('', (string) $uri->path());
        $this->assertSame('', (string) $uri->query());
        $this->assertNull($uri->fragment());
    }

    /**
     * @test
     */
    public function absolute(): void
    {
        $this->assertTrue($this->uriFor('https://example.com/foo')->isAbsolute());
        $this->assertFalse($this->uriFor('example.com/foo')->isAbsolute());
        $this->assertFalse($this->uriFor('/foo')->isAbsolute());
    }

    /**
     * @test
     */
    public function can_get_extension(): void
    {
        $this->assertNull($this->uriFor('https://example.com/foo')->path()->extension());
        $this->assertNull($this->uriFor('https://example.com/')->path()->extension());
        $this->assertNull($this->uriFor('https://example.com')->path()->extension());
        $this->assertNull($this->uriFor('')->path()->extension());
        $this->assertSame('txt', $this->uriFor('https://example.com/foo.txt')->path()->extension());
        $this->assertSame('txt', $this->uriFor('/foo.txt')->path()->extension());
        $this->assertSame('txt', $this->uriFor('file:///foo.txt')->path()->extension());
        $this->assertSame('0', $this->uriFor('file:///foo.0')->path()->extension());
    }

    /**
     * @test
     */
    public function can_get_dirname(): void
    {
        $this->assertNull($this->uriFor('https://example.com/')->path()->dirname());
        $this->assertNull($this->uriFor('https://example.com')->path()->dirname());
        $this->assertNull($this->uriFor('https://example.com/foo.txt')->path()->dirname());
        $this->assertNull($this->uriFor('/foo.txt')->path()->dirname());
        $this->assertNull($this->uriFor('file:///foo.txt')->path()->dirname());
        $this->assertNull($this->uriFor('')->path()->dirname());
        $this->assertNull($this->uriFor('/')->path()->dirname());
        $this->assertSame('/foo/bar', $this->uriFor('https://example.com/foo/bar/baz.txt')->path()->dirname());
        $this->assertSame('/foo/bar', $this->uriFor('https://example.com/foo/bar/baz')->path()->dirname());
        $this->assertSame('/foo/bar', $this->uriFor('https://example.com/foo/bar/baz/')->path()->dirname());
        $this->assertSame('/0', $this->uriFor('https://example.com/0/1')->path()->dirname());
    }

    /**
     * @test
     */
    public function can_get_filename_without_extension(): void
    {
        $this->assertNull($this->uriFor('https://example.com/')->path()->filenameWithoutExtension());
        $this->assertNull($this->uriFor('https://example.com')->path()->filenameWithoutExtension());
        $this->assertSame('foo', $this->uriFor('https://example.com/foo.txt')->path()->filenameWithoutExtension());
        $this->assertSame('foo', $this->uriFor('/foo.txt')->path()->filenameWithoutExtension());
        $this->assertSame('foo', $this->uriFor('file:///foo.txt')->path()->filenameWithoutExtension());
        $this->assertSame('baz', $this->uriFor('https://example.com/foo/bar/baz.txt')->path()->filenameWithoutExtension());
        $this->assertSame('baz', $this->uriFor('https://example.com/foo/bar/baz')->path()->filenameWithoutExtension());
        $this->assertSame('baz', $this->uriFor('https://example.com/foo/bar/baz/')->path()->filenameWithoutExtension());
        $this->assertSame('0', $this->uriFor('https://example.com/foo/bar/0')->path()->filenameWithoutExtension());
        $this->assertSame('0', $this->uriFor('https://example.com/foo/bar/0/')->path()->filenameWithoutExtension());
        $this->assertSame('0', $this->uriFor('https://example.com/foo/bar/0.txt')->path()->filenameWithoutExtension());
    }

    /**
     * @test
     */
    public function can_get_filename(): void
    {
        $this->assertNull($this->uriFor('https://example.com/')->path()->filename());
        $this->assertNull($this->uriFor('https://example.com')->path()->filename());
        $this->assertSame('foo.txt', $this->uriFor('https://example.com/foo.txt')->path()->filename());
        $this->assertSame('foo.txt', $this->uriFor('/foo.txt')->path()->filename());
        $this->assertSame('foo.txt', $this->uriFor('file:///foo.txt')->path()->filename());
        $this->assertSame('baz.txt', $this->uriFor('https://example.com/foo/bar/baz.txt')->path()->filename());
        $this->assertSame('baz', $this->uriFor('https://example.com/foo/bar/baz')->path()->filename());
        $this->assertSame('baz', $this->uriFor('https://example.com/foo/bar/baz/')->path()->filename());
        $this->assertSame('0', $this->uriFor('https://example.com/foo/0')->path()->filename());
        $this->assertSame('0', $this->uriFor('https://example.com/foo/0/')->path()->filename());
    }

    /**
     * @test
     */
    public function can_get_the_host_tld(): void
    {
        $this->assertNull($this->uriFor('/foo')->host()->tld());
        $this->assertNull($this->uriFor('http://localhost/foo')->host()->tld());
        $this->assertSame('com', $this->uriFor('https://example.com/foo')->host()->tld());
        $this->assertSame('com', $this->uriFor('https://sub1.sub2.example.com/foo')->host()->tld());
    }

    /**
     * @test
     */
    public function can_trim_path(): void
    {
        $this->assertSame('', $this->uriFor('http://localhost')->path()->trim()->toString());
        $this->assertSame('foo/bar', $this->uriFor('http://localhost/foo/bar')->path()->trim()->toString());
        $this->assertSame('foo/bar', $this->uriFor('http://localhost/foo/bar/')->path()->trim()->toString());
        $this->assertSame('', $this->uriFor('http://localhost')->path()->ltrim()->toString());
        $this->assertSame('foo/bar', $this->uriFor('http://localhost/foo/bar')->path()->ltrim()->toString());
        $this->assertSame('foo/bar/', $this->uriFor('http://localhost/foo/bar/')->path()->ltrim()->toString());
        $this->assertSame('', $this->uriFor('http://localhost')->path()->rtrim()->toString());
        $this->assertSame('/foo/bar', $this->uriFor('http://localhost/foo/bar')->path()->rtrim()->toString());
        $this->assertSame('/foo/bar', $this->uriFor('http://localhost/foo/bar/')->path()->rtrim()->toString());
    }

    /**
     * @test
     *
     * @dataProvider validAbsolutePaths
     */
    public function can_get_absolute_path($uri, $expected): void
    {
        $this->assertSame($expected, $this->uriFor($uri)->path()->absolute()->toString());
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
     *
     * @dataProvider invalidAbsolutePaths
     */
    public function cannot_get_absolute_path_outside_root($uri): void
    {
        $this->expectException(\RuntimeException::class);

        $this->uriFor($uri)->path()->absolute();
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
        $this->assertSame([], $this->uriFor('http://localhost')->path()->segments());
        $this->assertSame([], $this->uriFor('http://localhost/')->path()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('http://localhost/foo/bar')->path()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('http://localhost/foo/bar/')->path()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('/foo/bar/')->path()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('foo/bar')->path()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('foo/bar/')->path()->segments());

        $this->assertNull($this->uriFor('http://localhost')->path()->segment(0));
        $this->assertSame('default', $this->uriFor('http://localhost')->path()->segment(0, 'default'));
        $this->assertNull($this->uriFor('http://localhost')->path()->segment(2));
        $this->assertNull($this->uriFor('http://localhost/')->path()->segment(1));
        $this->assertSame('foo', $this->uriFor('http://localhost/foo/bar')->path()->segment(0));
        $this->assertSame('bar', $this->uriFor('http://localhost/foo/bar')->path()->segment(1));
        $this->assertNull($this->uriFor('http://localhost/foo/bar')->path()->segment(2));
    }

    /**
     * @test
     */
    public function can_get_scheme_segments(): void
    {
        $this->assertSame([], $this->uriFor('/foo')->scheme()->segments());
        $this->assertSame(['http'], $this->uriFor('http://localhost/foo/bar/')->scheme()->segments());
        $this->assertSame(['foo', 'bar'], $this->uriFor('foo+bar://localhost/foo/bar')->scheme()->segments());

        $this->assertNull($this->uriFor('/foo')->scheme()->segment(0));
        $this->assertSame('default', $this->uriFor('/foo')->scheme()->segment(0, 'default'));
        $this->assertNull($this->uriFor('/foo')->scheme()->segment(2));
        $this->assertSame('foo', $this->uriFor('foo://localhost')->scheme()->segment(0));
        $this->assertSame('foo', $this->uriFor('foo+bar://localhost')->scheme()->segment(0));
        $this->assertSame('bar', $this->uriFor('foo+bar://localhost')->scheme()->segment(1));
        $this->assertNull($this->uriFor('foo+bar://localhost')->scheme()->segment(2));
    }

    /**
     * @test
     */
    public function scheme_equals(): void
    {
        $this->assertFalse($this->uriFor('/foo')->scheme()->equals('http'));
        $this->assertTrue($this->uriFor('/foo')->scheme()->equals(''));
        $this->assertTrue($this->uriFor('http://localhost/foo')->scheme()->equals('http'));
        $this->assertFalse($this->uriFor('http://localhost/foo')->scheme()->equals('https'));
    }

    /**
     * @test
     */
    public function scheme_in(): void
    {
        $this->assertFalse($this->uriFor('/foo')->scheme()->in(['http', 'https']));
        $this->assertTrue($this->uriFor('/foo')->scheme()->in(['http', 'https', '']));
        $this->assertTrue($this->uriFor('http://localhost/foo')->scheme()->in(['http', 'https']));
        $this->assertFalse($this->uriFor('ftp://localhost/foo')->scheme()->in(['http', 'https']));
    }

    /**
     * @test
     */
    public function scheme_contains(): void
    {
        $this->assertFalse($this->uriFor('/foo')->scheme()->contains('ftp'));
        $this->assertTrue($this->uriFor('foo+bar://localhost/foo')->scheme()->contains('foo'));
        $this->assertTrue($this->uriFor('foo+bar://localhost/foo')->scheme()->contains('bar'));
        $this->assertFalse($this->uriFor('foo+bar://localhost/foo')->scheme()->contains('ftp'));
    }

    /**
     * @test
     */
    public function can_get_host_segments(): void
    {
        $this->assertSame([], $this->uriFor('/foo')->host()->segments());
        $this->assertSame(['localhost'], $this->uriFor('http://localhost/foo/bar/')->host()->segments());
        $this->assertSame(['local', 'host'], $this->uriFor('http://local.host/foo/bar')->host()->segments());

        $this->assertNull($this->uriFor('/foo')->host()->segment(0));
        $this->assertSame('default', $this->uriFor('/foo')->host()->segment(1, 'default'));
        $this->assertNull($this->uriFor('/foo')->host()->segment(1));
        $this->assertSame('localhost', $this->uriFor('http://localhost')->host()->segment(0));
        $this->assertSame('local', $this->uriFor('http://local.host')->host()->segment(0));
        $this->assertSame('host', $this->uriFor('http://local.host')->host()->segment(1));
        $this->assertNull($this->uriFor('http://local.host')->host()->segment(2));
    }

    /**
     * @test
     */
    public function can_read_query_array(): void
    {
        $uri = $this->uriFor('/');

        $this->assertSame([], $uri->query()->all());
        $this->assertNull($uri->query()->get('foo'));
        $this->assertSame('default', $uri->query()->get('foo', 'default'));
        $this->assertFalse($uri->query()->has('foo'));

        $uri = $this->uriFor('/?a=b&c=d');

        $this->assertSame(['a' => 'b', 'c' => 'd'], $uri->query()->all());
        $this->assertSame('d', $uri->query()->get('c'));
        $this->assertTrue($uri->query()->has('a'));
    }

    /**
     * @test
     */
    public function can_get_query_param_as_boolean(): void
    {
        $this->assertTrue($this->uriFor('/?foo=true')->query()->getBool('foo'));
        $this->assertTrue($this->uriFor('/?foo=1')->query()->getBool('foo'));
        $this->assertFalse($this->uriFor('/?foo=false')->query()->getBool('foo'));
        $this->assertFalse($this->uriFor('/?foo=0')->query()->getBool('foo'));
        $this->assertFalse($this->uriFor('/?foo=something')->query()->getBool('foo'));
        $this->assertTrue($this->uriFor('/')->query()->getBool('foo', true));
        $this->assertFalse($this->uriFor('/')->query()->getBool('foo', false));
        $this->assertFalse($this->uriFor('/?foo[]=bar')->query()->getBool('foo', true));
    }

    /**
     * @test
     */
    public function can_get_query_param_as_int(): void
    {
        $this->assertSame(5, $this->uriFor('/?foo=5')->query()->getInt('foo'));
        $this->assertSame(0, $this->uriFor('/?foo=something')->query()->getInt('foo'));
        $this->assertSame(0, $this->uriFor('/')->query()->getInt('foo'));
        $this->assertSame(1, $this->uriFor('/?foo[]=bar')->query()->getInt('foo'));
        $this->assertSame(3, $this->uriFor('/')->query()->getInt('foo', 3));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        $this->uriFor('/?foo=5')->query()->get('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_bool_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        $this->uriFor('/?foo=5')->query()->getBool('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     */
    public function can_pass_throwable_to_query_get_int_default(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid');

        $this->uriFor('/?foo=5')->query()->getInt('bar', new \RuntimeException('invalid'));
    }

    /**
     * @test
     *
     * @dataProvider guessPortProvider
     */
    public function can_guess_port(string $uri, ?int $expectedPort): void
    {
        $this->assertSame($expectedPort, $this->uriFor($uri)->guessPort());
    }

    public static function guessPortProvider(): iterable
    {
        yield ['http://example.com', 80];
        yield ['http://example.com:21', 21];
        yield ['https://example.com', 443];
        yield ['ftp://example.com', 21];
        yield ['ftps://example.com', 21];
        yield ['sftp://example.com', 22];
        yield ['gopher://example.com', 70];
        yield ['ws://example.com', 80];
        yield ['wss://example.com', 443];
        yield ['unk://example.com', null];
        yield ['/path', null];
    }

    abstract protected function uriFor(string $value): Uri;
}
