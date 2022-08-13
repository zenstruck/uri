<?php

namespace Zenstruck\Uri;

use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\UriAlreadyUsed;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUri extends Uri
{
    public const EXPIRES_AT_KEY = '_expires';
    public const SINGLE_USE_TOKEN_KEY = '_token';

    /**
     * @param string|UriSigner $secret
     * @param string|null      $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *
     * @throws ExpiredUri       if the URI has expired
     * @throws UriAlreadyUsed   if the URI has already been used
     * @throws InvalidSignature if the URI could not be verified
     */
    public function verify($secret, ?string $singleUseToken = null): void
    {
        if (!\class_exists(UriSigner::class)) {
            throw new \LogicException('symfony/http-kernel is required to verify signed URIs. composer require symfony/http-kernel.');
        }

        $signer = $secret instanceof UriSigner ? $secret : new UriSigner($secret);

        if (!$signer->check($this)) {
            throw new InvalidSignature($this);
        }

        $expiresAt = $this->expiresAt();

        if ($expiresAt && $expiresAt < new \DateTimeImmutable('now')) {
            throw new ExpiredUri($this);
        }

        $singleUseSignature = $this->query()->get(self::SINGLE_USE_TOKEN_KEY);

        if (!$singleUseSignature && !$singleUseToken) {
            return;
        }

        if ($singleUseSignature && !$singleUseToken) {
            throw new InvalidSignature($this, 'URI is single use but this was not expected.');
        }

        if (!$singleUseSignature && $singleUseToken) { // @phpstan-ignore-line
            throw new InvalidSignature($this, 'Expected single use URI.');
        }

        // hack to get the correct parameter used
        $parameter = \Closure::bind(fn(UriSigner $signer) => $signer->parameter, null, $signer);

        // remove the _hash query parameter
        $uri = $this->withoutQueryParams($parameter($signer));

        if (!(new UriSigner($singleUseToken, self::SINGLE_USE_TOKEN_KEY))->check($uri)) { // @phpstan-ignore-line
            throw new UriAlreadyUsed($this);
        }
    }

    /**
     * @param string|UriSigner $secret
     * @param string|null      $singleUseToken If passed, this value MUST change once the URL is considered "used"
     */
    public function isVerified($secret, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($secret, $singleUseToken);

            return true;
        } catch (VerificationFailed $e) {
            return false;
        }
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        if ($timestamp = $this->query()->getInt(self::EXPIRES_AT_KEY)) {
            return \DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: null;
        }

        return null;
    }

    public function isTemporary(): bool
    {
        return $this->query()->has(self::EXPIRES_AT_KEY);
    }

    public function isSingleUse(): bool
    {
        return $this->query()->has(self::SINGLE_USE_TOKEN_KEY);
    }
}
