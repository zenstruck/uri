<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Part implements \Stringable
{
    final public function __toString(): string
    {
        return $this->toString();
    }

    final public function isEmpty(): bool
    {
        return '' === $this->toString();
    }

    abstract public function toString(): string;
}
