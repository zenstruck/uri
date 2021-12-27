<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Part
{
    use Stringable;

    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    final protected function generateString(): string
    {
        return $this->value;
    }
}
