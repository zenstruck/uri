<?php

namespace Zenstruck\Uri;

use Zenstruck\Uri;
use Zenstruck\Uri\Part\Authority;
use Zenstruck\Uri\Part\Path;
use Zenstruck\Uri\Part\Query;
use Zenstruck\Uri\Part\Scheme;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class WrappedUri extends Uri
{
    final public function scheme(): Scheme
    {
        return $this->inner()->scheme();
    }

    final public function authority(): Authority
    {
        return $this->inner()->authority();
    }

    final public function path(): Path
    {
        return $this->inner()->path();
    }

    final public function query(): Query
    {
        return $this->inner()->query();
    }

    final public function fragment(): ?string
    {
        return $this->inner()->fragment();
    }

    final public function toString(): string
    {
        return $this->inner()->toString();
    }

    abstract protected function inner(): Uri;
}
