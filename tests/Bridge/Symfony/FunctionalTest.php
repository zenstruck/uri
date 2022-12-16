<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Tests\Bridge\Symfony;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;
use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\Signed\Exception\Expired;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Tests\Bridge\Symfony\Fixture\TestKernel;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FunctionalTest extends WebTestCase
{
    /**
     * @test
     *
     * @dataProvider invalidSignedUrlProvider
     */
    public function invalid_signed_url(string $path, string $expectedException, ?int $expectedStatus = 0): void
    {
        $client = self::createClient();
        $client->catchExceptions(false);

        try {
            $client->request('GET', $path);
        } catch (\Throwable $e) {
            $this->assertSame($expectedException, $e::class);

            if ($e instanceof HttpException) {
                $this->assertSame($expectedStatus, $e->getStatusCode());
            }

            return;
        }

        $this->fail('no exception thrown.');
    }

    public static function invalidSignedUrlProvider(): iterable
    {
        yield ['/verify-with-route-option', InvalidSignature::class];
        yield [ParsedUri::wrap('http://localhost/verify-with-route-option')->sign(TestKernel::SECRET)->expires('yesterday')->create()->toString(), Expired::class];
        yield ['/verify-with-route-option-and-custom-status-code', HttpException::class, 404];
        yield [ParsedUri::wrap('http://localhost/verify-with-route-option-and-custom-status-code')->sign(TestKernel::SECRET)->expires('yesterday')->create()->toString(), HttpException::class, 404];
        yield ['/verify-with-route-attribute', InvalidSignature::class];
        yield ['/verify-with-route-attribute-and-custom-status-code', HttpException::class, 404];
        yield ['/prefix/method1', InvalidSignature::class];
        yield ['/prefix/method2', HttpException::class, 404];
        yield ['/method1', InvalidSignature::class];
        yield [ParsedUri::wrap('http://localhost/method1')->sign(TestKernel::SECRET)->singleUse('foo')->create()->toString(), InvalidSignature::class];
        yield ['/method2', HttpException::class, 404];
    }

    /**
     * @test
     *
     * @dataProvider validSignedUrlProvider
     */
    public function valid_signed_url(string $path): void
    {
        self::createClient()->request('GET', $path);

        self::assertResponseStatusCodeSame(200);
    }

    public static function validSignedUrlProvider(): iterable
    {
        yield ['/prefix/method3'];
        yield [ParsedUri::wrap('http://localhost/verify-with-route-option')->sign(TestKernel::SECRET)->expires('+30 mins')->create()->toString()];
    }

    /**
     * @test
     */
    public function use_signed_url_generator(): void
    {
        $client = self::createClient();

        /** @var SignedUrlGenerator $generator */
        $generator = self::getContainer()->get(SignedUrlGenerator::class);

        $client->request('GET', $generator->generate('verify-with-route-option'));
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', $generator->generate('verify-with-route-option', ['foo' => 'bar']));
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', $generator->temporary('tomorrow', 'verify-with-route-option'));
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', $generator->temporary('yesterday', 'verify-with-route-option'));
        self::assertResponseStatusCodeSame(500);

        $client->request('GET', $generator->build('verify-with-route-option')->singleUse('foo'));
        self::assertResponseStatusCodeSame(500);
    }

    /**
     * @test
     */
    public function use_signed_url_verifier(): void
    {
        $client = self::createClient();

        /** @var SignedUrlGenerator $generator */
        $generator = self::getContainer()->get(SignedUrlGenerator::class);

        $client->request('GET', $generator->generate('verify'));
        self::assertResponseStatusCodeSame(200);

        $client->request('GET', $generator->generate('is-verify'));
        self::assertResponseStatusCodeSame(200);
        $this->assertSame('Success', $client->getResponse()->getContent());

        $client->request('GET', '/is-verify');
        self::assertResponseStatusCodeSame(200);
        $this->assertSame('Failure', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function current_uri_twig_function(): void
    {
        $client = self::createClient();
        $client->request('GET', '/render/current_url');

        $this->assertStringContainsString('localhost', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function signed_uri_twig_function(): void
    {
        $client = self::createClient();
        $client->request('GET', '/render/signed_url');

        $this->assertStringContainsString('http://localhost/verify?_expires=', $client->getResponse()->getContent());
        $this->assertStringContainsString('_hash=', $client->getResponse()->getContent());
        $this->assertStringContainsString('_token=', $client->getResponse()->getContent());
    }
}
