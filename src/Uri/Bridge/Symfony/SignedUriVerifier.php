<?php

namespace Zenstruck\Uri\Bridge\Symfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUriVerifier
{
    private UriSigner $signer;
    private CurrentRequestUriFactory $factory;

    /**
     * @internal
     */
    public function __construct(UriSigner $signer, CurrentRequestUriFactory $factory)
    {
        $this->signer = $signer;
        $this->factory = $factory;
    }

    /**
     * @param string|Uri|Request $uri
     */
    public function verify($uri, ?string $singleUseToken = null): SignedUri
    {
        return Uri::new($uri)->verify($this->signer, $singleUseToken);
    }

    /**
     * @param string|Uri|Request $uri
     */
    public function isVerified($uri, ?string $singleUseToken = null): bool
    {
        return Uri::new($uri)->isVerified($this->signer, $singleUseToken);
    }

    public function verifyCurrentRequest(?string $singleUseToken = null): SignedUri
    {
        return $this->factory->create()->verify($this->signer, $singleUseToken);
    }

    public function isCurrentRequestVerified(?string $singleUseToken = null): bool
    {
        return $this->factory->create()->isVerified($this->signer, $singleUseToken);
    }
}
