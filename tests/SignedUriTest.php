<?php

namespace Zenstruck\Uri\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\UriAlreadyUsed;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUriTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider validSignedUrlProvider
     */
    public function can_sign_url($uri, $secret, $expiresAt = null, $singleUseToken = null): void
    {
        $uri = Uri::new($uri);
        $builder = $uri->sign($secret);

        if ($expiresAt) {
            $builder = $builder->expires($expiresAt);
        }

        if ($singleUseToken) {
            $builder = $builder->singleUse($singleUseToken);
        }

        $signed = $builder->create();

        $this->assertTrue(Uri::new($signed)->isVerified($secret, $singleUseToken));
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
        yield ['/foo/bar', new UriSigner('1234')];
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
        $uri = Uri::new($uri);

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
        $builder = Uri::new('/foo/bar')->sign('1234');

        yield [$builder->create(), '4321', InvalidSignature::class];
        yield [$builder->create(), new UriSigner('4321'), InvalidSignature::class];
        yield [$builder->expires(-5)->create(), '1234', ExpiredUri::class];
        yield [$builder->expires('yesterday')->create(), '1234', ExpiredUri::class];
        yield [$builder->singleUse('token')->create(), '1234', InvalidSignature::class];
        yield [$builder->create(), '1234', InvalidSignature::class, 'token'];
        yield [$builder->singleUse('token')->create(), '1234', UriAlreadyUsed::class, 'invalid'];
        yield [Request::create('foo/bar'), '4321', InvalidSignature::class];
    }

    /**
     * @test
     */
    public function can_access_expires_at(): void
    {
        $this->assertNull(Uri::new('/foo/bar')->sign('1234')->create()->expiresAt());

        $expiresAt = new \DateTime('tomorrow');

        $this->assertSame(
            $expiresAt->getTimestamp(),
            Uri::new('/foo/bar')->sign('1234')->expires($expiresAt)->create()->expiresAt()->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function cannot_create_with_invalid_expires_string(): void
    {
        $this->expectException(\Exception::class);

        Uri::new('/foo')->sign('1234')->expires('invalid');
    }

    /**
     * @test
     */
    public function cannot_create_with_invalid_expires_object(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uri::new('/foo')->sign('1234')->expires(new \stdClass());
    }

    /**
     * @test
     */
    public function cannot_be_cloned(): void
    {
        $uri = Uri::new('/foo')->sign('foo')->create();

        $this->expectException(\LogicException::class);

        clone $uri;
    }

    /**
     * @test
     */
    public function cannot_re_sign(): void
    {
        $uri = Uri::new('/foo')->sign('foo')->create();

        $this->expectException(\LogicException::class);

        $uri->sign('secret');
    }

    /**
     * @test
     */
    public function cannot_re_verify(): void
    {
        $uri = Uri::new('/foo')->sign('foo')->create();

        $this->expectException(\LogicException::class);

        $uri->verify('secret');
    }

    /**
     * @test
     */
    public function cannot_check_if_verified(): void
    {
        $uri = Uri::new('/foo')->sign('foo')->create();

        $this->expectException(\LogicException::class);

        $uri->isVerified('secret');
    }

    /**
     * @test
     */
    public function cannot_call_new_normally(): void
    {
        $this->expectException(\LogicException::class);

        SignedUri::new('/foo/bar');
    }
}
