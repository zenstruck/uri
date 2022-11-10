<?php

namespace Zenstruck\Uri;

use Zenstruck\Uri;
use Zenstruck\Uri\Part\Host;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class BaseUri implements Uri
{
    final public function __toString(): string
    {
        return $this->toString();
    }

    final public function username(): ?string
    {
        return $this->authority()->username();
    }

    final public function password(): ?string
    {
        return $this->authority()->password();
    }

    final public function host(): Host
    {
        return $this->authority()->host();
    }

    final public function port(): ?int
    {
        return $this->authority()->port();
    }

    final public function guessPort(): ?int
    {
        return $this->port() ?? $this->scheme()->defaultPort();
    }

    final public function isAbsolute(): bool
    {
        return !$this->scheme()->isEmpty();
    }
}
