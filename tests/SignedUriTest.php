<?php

namespace Zenstruck\Uri\Tests;

use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\Signed\Exception\AlreadyUsed;
use Zenstruck\Uri\Signed\Exception\Expired;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUriTest extends UriTest
{
    /**
     * @test
     *
     * @dataProvider validSignedUrlProvider
     */
    public function can_sign_url($uri, $secret, $expiresAt = null, $singleUseToken = null): void
    {
        $builder = ParsedUri::wrap($uri)->sign($secret);

        if ($expiresAt) {
            $builder = $builder->expires($expiresAt);
        }

        if ($singleUseToken) {
            $builder = $builder->singleUse($singleUseToken);
        }

        $signed = $builder->create();

        $this->assertTrue(ParsedUri::wrap($signed)->isVerified($secret, $singleUseToken));
        $this->assertSame((string) $builder, (string) $signed);
        $this->assertTrue($signed->query()->has('_hash'));

        if ($expiresAt) {
            $this->assertTrue($signed->isTemporary());
            $this->assertTrue($signed->query()->has('_expires'));
        } else {
            $this->assertFalse($signed->isTemporary());
            $this->assertFalse($signed->query()->has('_expires'));
        }

        if ($singleUseToken) {
            $this->assertTrue($signed->isSingleUse());
            $this->assertTrue($signed->query()->has('_token'));
        } else {
            $this->assertFalse($signed->isSingleUse());
            $this->assertFalse($signed->query()->has('_token'));
        }
    }

    public static function validSignedUrlProvider(): iterable
    {
        yield ['/foo/bar', '1234'];
        yield ['http://example.com/foo/bar?baz=1', '1234'];
        yield ['/foo/bar', '1234', 5];
        yield ['/foo/bar', '1234', 'tomorrow'];
        yield ['/foo/bar', '1234', new \DateTime('+1 hour')];
        yield ['/foo/bar', '1234', \DateInterval::createFromDateString('+1 hour')];
        yield ['/foo/bar', '1234', null, 'token'];
        yield ['/foo/bar', '1234', 5, 'token'];
    }

    /**
     * @test
     *
     * @dataProvider invalidSignedUrlProvider
     */
    public function invalid_signed_url($uri, $secret, $expectedException, $singleUseToken = null): void
    {
        $uri = ParsedUri::wrap($uri);

        $this->assertFalse($uri->isVerified($secret, $singleUseToken));

        try {
            $uri->verify($secret, $singleUseToken);
        } catch (VerificationFailed $e) {
            $this->assertInstanceOf($expectedException, $e);
            $this->assertSame($uri, $e->uri());

            return;
        }

        $this->fail('URI was verified.');
    }

    public static function invalidSignedUrlProvider(): iterable
    {
        $builder = ParsedUri::wrap('/foo/bar')->sign('1234');

        yield [$builder->create(), '4321', InvalidSignature::class];
        yield [$builder->expires(-5)->create(), '1234', Expired::class];
        yield [$builder->expires('yesterday')->create(), '1234', Expired::class];
        yield [$builder->singleUse('token')->create(), '1234', InvalidSignature::class];
        yield [$builder->create(), '1234', InvalidSignature::class, 'token'];
        yield [$builder->singleUse('token')->create(), '1234', AlreadyUsed::class, 'invalid'];
    }

    /**
     * @test
     */
    public function can_access_expires_at(): void
    {
        $this->assertNull(ParsedUri::wrap('/foo/bar')->sign('1234')->create()->expiresAt());

        $expiresAt = new \DateTime('tomorrow');

        $this->assertSame(
            $expiresAt->getTimestamp(),
            ParsedUri::wrap('/foo/bar')->sign('1234')->expires($expiresAt)->create()->expiresAt()?->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function cannot_create_with_invalid_expires_string(): void
    {
        $this->expectException(\Exception::class);

        ParsedUri::wrap('/foo')->sign('1234')->expires('invalid');
    }

    protected function uriFor(string $value): SignedUri
    {
        $class = new \ReflectionClass(SignedUri::class);
        $uri = $class->newInstanceWithoutConstructor();
        $prop = $class->getProperty('uri');
        $prop->setAccessible(true);
        $prop->setValue($uri, new ParsedUri($value));

        return $uri;
    }
}
