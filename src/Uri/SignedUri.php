<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri;

use Zenstruck\Uri;
use Zenstruck\Uri\Signed\Exception\AlreadyUsed;
use Zenstruck\Uri\Signed\Exception\Expired;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\SymfonySigner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUri extends WrappedUri
{
    private function __construct(private Uri $uri, private ?\DateTimeImmutable $expiresAt, private bool $singleUse)
    {
    }

    public static function sign(string|Uri $uri, string|SymfonySigner $secret, ?\DateTimeImmutable $expiresAt = null, ?string $singleUseToken = null): self
    {
        return new self(...SymfonySigner::create($secret)->sign($uri, $expiresAt, $singleUseToken));
    }

    /**
     * @param string|null $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *
     * @throws Expired          if the URI has expired
     * @throws AlreadyUsed      if the URI has already been used
     * @throws InvalidSignature if the URI could not be verified
     */
    public static function verify(string|Uri $uri, string|SymfonySigner $secret, ?string $singleUseToken = null): self
    {
        return new self(...SymfonySigner::create($secret)->verify($uri, $singleUseToken));
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
        return $this->singleUse;
    }

    protected function inner(): Uri
    {
        return $this->uri;
    }
}
