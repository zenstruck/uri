<?php

namespace Zenstruck\Uri;

use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Signed\Builder;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\UriAlreadyUsed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUri extends Uri
{
    private const EXPIRES_AT_KEY = '_expires';
    private const SINGLE_USE_TOKEN_KEY = '_token';

    private ?\DateTimeImmutable $expiresAt;

    /**
     * @param string|Uri $uri
     */
    private function __construct($uri, ?\DateTimeImmutable $expiresAt)
    {
        $this->expiresAt = $expiresAt;

        parent::__construct($uri);
    }

    public function __clone()
    {
        throw new \LogicException(\sprintf('%s (%s) cannot be cloned.', self::class, $this));
    }

    /**
     * @internal
     *
     * @param Builder $builder
     */
    public static function new($builder = null): self
    {
        if (!$builder instanceof Builder) {
            throw new \LogicException(\sprintf('"%s" is internal and cannot be called directly.', __METHOD__));
        }

        [$uri, $signer, $expiresAt, $singleUseToken] = $builder->context();

        if ($expiresAt) {
            $uri = $uri->withQueryParam(self::EXPIRES_AT_KEY, $expiresAt->getTimestamp());
        }

        if ($singleUseToken) {
            $uri = (new UriSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY))->sign($uri);
        }

        return new self($signer->sign($uri), $expiresAt);
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isTemporary(): bool
    {
        return $this->expiresAt instanceof \DateTimeImmutable;
    }

    public function isSingleUse(): bool
    {
        return $this->query()->has(self::SINGLE_USE_TOKEN_KEY);
    }

    /**
     * @param string|UriSigner $secret
     */
    protected static function createVerified(Uri $uri, $secret, ?string $singleUseToken): self
    {
        if (!\class_exists(UriSigner::class)) {
            throw new \LogicException('symfony/http-kernel is required to verify signed URIs. composer require symfony/http-kernel.');
        }

        if ($uri instanceof self) {
            throw new \LogicException(\sprintf('"%s" is already signed.', $uri));
        }

        $signer = $secret instanceof UriSigner ? $secret : new UriSigner($secret);

        if (!$signer->check($uri)) {
            throw new InvalidSignature($uri);
        }

        $expiresAt = self::calculateExpiresAt($uri);

        if ($expiresAt && $expiresAt < new \DateTimeImmutable('now')) {
            throw new ExpiredUri($uri, $expiresAt);
        }

        $singleUseSignature = $uri->query()->get(self::SINGLE_USE_TOKEN_KEY);

        if (!$singleUseSignature && !$singleUseToken) {
            return new self($uri, $expiresAt);
        }

        if ($singleUseSignature && !$singleUseToken) {
            throw new InvalidSignature($uri, 'URI is single use but this was not expected.');
        }

        if (!$singleUseSignature && $singleUseToken) { // @phpstan-ignore-line
            throw new InvalidSignature($uri, 'Expected single use URI.');
        }

        // hack to get the correct parameter used
        $parameter = \Closure::bind(fn(UriSigner $signer) => $signer->parameter, null, $signer);

        // remove the _hash query parameter
        $withoutHash = $uri->withoutQueryParams($parameter($signer));

        if (!(new UriSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY))->check($withoutHash)) { // @phpstan-ignore-line
            throw new UriAlreadyUsed($uri);
        }

        return new self($uri, $expiresAt);
    }

    private static function calculateExpiresAt(Uri $uri): ?\DateTimeImmutable
    {
        if ($timestamp = $uri->query()->getInt(self::EXPIRES_AT_KEY)) {
            return \DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: null;
        }

        return null;
    }
}
