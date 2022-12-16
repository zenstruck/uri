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
