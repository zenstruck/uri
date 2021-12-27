<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class LowercasePart extends Part
{
    public function __construct(string $value)
    {
        parent::__construct(\mb_strtolower($value));
    }
}
