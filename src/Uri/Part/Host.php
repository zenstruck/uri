<?php

namespace Zenstruck\Uri\Part;

use Zenstruck\Uri\Part;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Host extends Part
{
    private string $value;

    public function __construct(?string $value)
    {
        $this->value = \mb_strtolower((string) $value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return string[] The host exploded with "."
     */
    public function segments(): array
    {
        return \array_filter(\explode('.', $this->value));
    }

    /**
     * @param int $index 0-based
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        return $this->segments()[$index] ?? $default;
    }

    public function tld(): ?string
    {
        $segments = $this->segments();
        $count = \count($segments);

        return \in_array($count, [0, 1], true) ? null : $segments[$count - 1];
    }
}
