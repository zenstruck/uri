<?php

namespace Zenstruck;

use Zenstruck\Uri\Part\Authority;
use Zenstruck\Uri\Part\Host;
use Zenstruck\Uri\Part\Path;
use Zenstruck\Uri\Part\Query;
use Zenstruck\Uri\Part\Scheme;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Uri implements \Stringable
{
    final public function __toString(): string
    {
        return $this->toString();
    }

    abstract public function scheme(): Scheme;

    abstract public function authority(): Authority;

    /**
     * @return string|null "urldecoded"
     */
    final public function username(): ?string
    {
        return $this->authority()->username();
    }

    /**
     * @return string|null "urldecoded"
     */
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

    abstract public function path(): Path;

    abstract public function query(): Query;

    /**
     * @return string|null "urldecoded"
     */
    abstract public function fragment(): ?string;

    /**
     * @return int|null The explicit port or the default for the scheme
     */
    final public function guessPort(): ?int
    {
        return $this->port() ?? $this->scheme()->defaultPort();
    }

    final public function isAbsolute(): bool
    {
        return !$this->scheme()->isEmpty();
    }

    abstract public function toString(): string;
}
