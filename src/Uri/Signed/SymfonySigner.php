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

use Symfony\Component\HttpKernel\UriSigner;
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

    private UriSigner $signer;

    public function __construct(string $secret)
    {
        if (!\class_exists(UriSigner::class)) {
            throw new \LogicException('symfony/http-kernel is required to sign URIs. Install with "composer require symfony/http-kernel".');
        }

        $this->signer = new UriSigner($secret, self::HASH_KEY);
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
            $uri = (new UriSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY))->sign($uri);
        }

        return [new ParsedUri($this->signer->sign($uri)), $expiresAt, (bool) $singleUseToken];
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

        if (!$this->signer->check($uri)) {
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

        if (!(new UriSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY))->check($withoutHash)) {
            throw new AlreadyUsed($uri);
        }

        return [$uri, $expiresAt, true];
    }
}
