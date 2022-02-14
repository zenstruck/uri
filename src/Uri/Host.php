<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Host extends LowercasePart
{
    /**
     * @return string[] The host exploded with "."
     */
    public function segments(): array
    {
        return \array_filter(\explode('.', $this->toString()));
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

        return \in_array(\count($segments), [0, 1], true) ? null : $segments[\count($segments) - 1];
    }
}
