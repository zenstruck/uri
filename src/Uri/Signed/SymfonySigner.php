<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Signed;

use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\UriSigner as LegacyUriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\Signed\Exception\AlreadyUsed;
use Zenstruck\Uri\Signed\Exception\Expired;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SymfonySigner
{
    private const EXPIRES_AT_KEY = '_expires';
    private const SINGLE_USE_TOKEN_KEY = '_token';
    private const HASH_KEY = '_hash';

    /** @var UriSigner|LegacyUriSigner */
    private $signer; // @phpstan-ignore-line

    public function __construct(string $secret)
    {
        $this->signer = self::createSigner($secret, self::HASH_KEY);
    }

    public static function create(self|string $secret): self
    {
        return $secret instanceof self ? $secret : new self($secret);
    }

    /**
     * @internal
     *
     * @return array{0:Uri,1:\DateTimeImmutable|null,2:bool}
     */
    public function sign(Uri|string $uri, ?\DateTimeImmutable $expiresAt, ?string $singleUseToken): array
    {
        $uri = ParsedUri::wrap($uri);

        if ($expiresAt) {
            $uri = $uri->withQueryParam(self::EXPIRES_AT_KEY, $expiresAt->getTimestamp());
        }

        if ($singleUseToken) {
            $uri = self::createSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY)->sign($uri); // @phpstan-ignore-line
        }

        return [new ParsedUri($this->signer->sign($uri)), $expiresAt, (bool) $singleUseToken]; // @phpstan-ignore-line
    }

    /**
     * @internal
     *
     * @return array{0:Uri,1:\DateTimeImmutable|null,2:bool}
     */
    public function verify(Uri|string $uri, ?string $singleUseToken): array
    {
        $uri = ParsedUri::wrap($uri);
        $expiresAt = null;

        if (!$this->signer->check($uri)) { // @phpstan-ignore-line
            throw new InvalidSignature($uri);
        }

        if ($timestamp = $uri->query()->getInt(self::EXPIRES_AT_KEY)) {
            $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: null;
        }

        if ($expiresAt && $expiresAt < new \DateTimeImmutable('now')) {
            throw new Expired($uri, $expiresAt);
        }

        $singleUseSignature = $uri->query()->get(self::SINGLE_USE_TOKEN_KEY);

        if (!$singleUseSignature && !$singleUseToken) { // @phpstan-ignore-line
            return [$uri, $expiresAt, false];
        }

        if ($singleUseSignature && !$singleUseToken) { // @phpstan-ignore-line
            throw new InvalidSignature($uri, 'URI is single use but this was not expected.');
        }

        if (!$singleUseSignature && $singleUseToken) { // @phpstan-ignore-line
            throw new InvalidSignature($uri, 'Expected single use URI.');
        }

        $withoutHash = $uri->withoutQueryParams(self::HASH_KEY); // @phpstan-ignore-line

        if (!self::createSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY)->check($withoutHash)) {
            throw new AlreadyUsed($uri);
        }

        return [$uri, $expiresAt, true];
    }

    private static function createSigner(#[\SensitiveParameter] string $secret, string $parameter = '_hash'): UriSigner|LegacyUriSigner // @phpstan-ignore-line
    {
        if (\class_exists(UriSigner::class)) {
            return new UriSigner($secret, $parameter);
        }

        if (\class_exists(LegacyUriSigner::class)) {
            return new LegacyUriSigner($secret, $parameter);
        }

        throw new \LogicException('symfony/http-foundation is required to sign URIs. Install with "composer require symfony/http-foundation".');
    }
}
