<?php

namespace Zenstruck\Uri\Signed;

use Zenstruck\Uri;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Builder implements \Stringable
{
    private ?\DateTimeImmutable $expiresAt = null;
    private ?string $singleUseToken = null;

    public function __construct(private Uri $uri, private string|SymfonySigner $secret)
    {
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
    public function expires(\DateTimeInterface|\DateInterval|string|int $when): self
    {
        if (\is_numeric($when)) {
            $when = \DateTimeImmutable::createFromFormat('U', (string) (\time() + $when));
        }

        if (\is_string($when)) {
            $when = new \DateTimeImmutable($when);
        }

        if ($when instanceof \DateInterval) {
            $when = (new \DateTime('now'))->add($when);
        }

        if ($when instanceof \DateTime) {
            $when = \DateTimeImmutable::createFromMutable($when);
        }

        if (!$when instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException(\sprintf('%s is not a valid expires at.', \get_debug_type($when)));
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
        return SignedUri::sign($this->uri, $this->secret, $this->expiresAt, $this->singleUseToken);
    }
}
