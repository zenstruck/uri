<?php

namespace Zenstruck\Uri\Bridge\Symfony\Routing;

use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Uri;
use Zenstruck\Uri\Bridge\Symfony\RequestUriFactory;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;
use Zenstruck\Uri\Signed\SymfonySigner;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlVerifier
{
    /**
     * @internal
     */
    public function __construct(private SymfonySigner $signer, private RequestUriFactory $factory)
    {
    }

    public function verify(Request|Uri|string $uri, ?string $singleUseToken = null): SignedUri
    {
        if ($uri instanceof Request) {
            $uri = $this->factory->create($uri);
        }

        return SignedUri::verify($uri, $this->signer, $singleUseToken);
    }

    public function isVerified(Request|Uri|string $uri, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($uri, $singleUseToken);

            return true;
        } catch (VerificationFailed) {
            return false;
        }
    }

    public function verifyCurrentRequest(?string $singleUseToken = null): SignedUri
    {
        return $this->verify($this->factory->create(), $singleUseToken);
    }

    public function isCurrentRequestVerified(?string $singleUseToken = null): bool
    {
        return $this->isVerified($this->factory->create(), $singleUseToken);
    }
}
