<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait Stringable
{
    private string $cachedString;

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __clone()
    {
        unset($this->cachedString);
    }

    public function isEmpty(): bool
    {
        return '' === $this->toString();
    }

    public function toString(): string
    {
        return $this->cachedString ??= $this->generateString();
    }

    abstract protected function generateString(): string;
}
