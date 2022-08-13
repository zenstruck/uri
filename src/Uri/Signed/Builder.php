<?php

namespace Zenstruck\Uri\Signed;

use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Builder implements \Stringable
{
    private Uri $uri;
    private UriSigner $signer;
    private ?\DateTimeInterface $expiresAt = null;
    private ?string $singleUseToken = null;

    /**
     * @param string|UriSigner $secret
     */
    public function __construct(Uri $uri, $secret)
    {
        if (!\class_exists(UriSigner::class)) {
            throw new \LogicException('symfony/http-kernel is required to sign URIs. composer require symfony/http-kernel.');
        }

        $this->uri = $uri;
        $this->signer = $secret instanceof UriSigner ? $secret : new UriSigner($secret);
    }

    public function __toString(): string
    {
        return $this->create();
    }

    /**
     * Set an expiry for the signed url.
     *
     * @param \DateTimeInterface|\DateInterval|string|int $when \DateTimeInterface: the exact time the link should expire
     *                                                          \DateInterval: the interval to be added to the current time
     *                                                          string: used to construct a datetime object (ie "+1 hour")
     *                                                          int: # of seconds until the link expires
     */
    public function expires($when): self
    {
        if (\is_numeric($when)) {
            $when = \DateTime::createFromFormat('U', (string) (\time() + $when));
        }

        if (\is_string($when)) {
            $when = new \DateTime($when);
        }

        if ($when instanceof \DateInterval) {
            $when = (new \DateTime('now'))->add($when);
        }

        if (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid expires at.', get_debug_type($when)));
        }

        $clone = clone $this;
        $clone->expiresAt = $when;

        return $clone;
    }

    /**
     * Make the signed url "single-use".
     *
     * @param string $token This value MUST change once the URL is considered "used"
     */
    public function singleUse(string $token): self
    {
        $clone = clone $this;
        $clone->singleUseToken = $token;

        return $clone;
    }

    public function create(): SignedUri
    {
        $uri = $this->uri;

        if ($this->expiresAt) {
            $uri = $uri->withQueryParam(SignedUri::EXPIRES_AT_KEY, $this->expiresAt->getTimestamp());
        }

        if ($this->singleUseToken) {
            $uri = (new UriSigner($this->singleUseToken, SignedUri::SINGLE_USE_TOKEN_KEY))->sign($uri);
        }

        return new SignedUri($this->signer->sign($uri));
    }
}
