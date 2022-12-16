<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Part;

use Zenstruck\Uri\Part;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Path extends Part
{
    private string $value;

    public function __construct(?string $value)
    {
        $this->value = self::decode((string) $value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function encode(): string
    {
        return \implode('/', \array_map('rawurlencode', \explode('/', $this->toString())));
    }

    /**
     * @return string[] The path segments "urldecoded"
     */
    public function segments(): array
    {
        return \array_values(\array_filter(\explode('/', $this->value)));
    }

    /**
     * @param int $index 0-based
     *
     * @return ?string The "urldecoded" segment
     */
    public function segment(int $index, ?string $default = null): ?string
    {
        return $this->segments()[$index] ?? $default;
    }

    public function trim(): self
    {
        $clone = clone $this;
        $clone->value = \trim($this->value, '/');

        return $clone;
    }

    public function rtrim(): self
    {
        $clone = clone $this;
        $clone->value = \rtrim($this->value, '/');

        return $clone;
    }

    public function ltrim(): self
    {
        $clone = clone $this;
        $clone->value = \ltrim($this->value, '/');

        return $clone;
    }

    /**
     * @throws \RuntimeException if path outside of root (ie /../)
     */
    public function absolute(): self
    {
        // todo is regex faster?
        if ($this->isAbsolute() && !\str_contains($this->value, '/.') && !\str_contains($this->value, '//')) {
            return $this;
        }

        $path = \explode('/', $this->value);
        $stack = [];

        foreach ($path as $segment) {
            $segment = \trim($segment);

            switch (true) {
                case '..' === $segment && empty($stack):
                    throw new \RuntimeException(\sprintf('Cannot resolve absolute path for "%s". It is outside of the root.', $this->value));
                case '..' === $segment:
                    \array_pop($stack);

                    continue 2;
                case '.' === $segment:
                case '' === $segment:
                    continue 2;
            }

            $stack[] = $segment;
        }

        $stack = \array_filter($stack);
        $trailingSlash = \count($stack) && '/' === \mb_substr($this->value, -1) ? '/' : '';

        $clone = clone $this;
        $clone->value = '/'.\ltrim(\implode('/', $stack), '/').$trailingSlash;

        return $clone;
    }

    /**
     * @example If path is "foo/bar/baz.txt", returns "txt"
     * @example If path is "foo/bar/baz", returns null
     */
    public function extension(): ?string
    {
        return '' === ($extension = \pathinfo($this->value, \PATHINFO_EXTENSION)) ? null : $extension;
    }

    /**
     * @example If path is "foo/bar/baz.txt", returns "foo/bar"
     * @example If path is "foo/bar/baz", returns "foo/baz"
     */
    public function dirname(): ?string
    {
        return \in_array($dirname = \pathinfo($this->value, \PATHINFO_DIRNAME), ['', '.', '/'], true) ? null : $dirname;
    }

    /**
     * @example If path is "foo/bar/baz.txt", returns "baz.txt"
     * @example If path is "foo/bar/baz", returns "baz"
     */
    public function filename(): ?string
    {
        return '' === ($filename = \pathinfo($this->value, \PATHINFO_BASENAME)) ? null : $filename;
    }

    /**
     * @example If path is "foo/bar/baz.txt", returns "baz"
     * @example If path is "foo/bar/baz", returns "baz"
     */
    public function filenameWithoutExtension(): ?string
    {
        // TODO support multi-extensions (ie tar.gz & tar.bz2)
        return '' === ($filename = \pathinfo($this->value, \PATHINFO_FILENAME)) ? null : $filename;
    }

    public function isAbsolute(): bool
    {
        return \str_starts_with($this->value, '/');
    }

    public function isDirectory(): bool
    {
        return \str_ends_with($this->value, '/');
    }

    public function append(string $path): self
    {
        if ('' === $path) {
            return $this;
        }

        if ($this->isEmpty()) {
            return new self($path);
        }

        $clone = clone $this;
        $clone->value = \rtrim($this->value, '/').'/'.\ltrim(self::decode($path), '/');

        return $clone;
    }

    public function prepend(string $path): self
    {
        if ('' === $path) {
            return $this;
        }

        if ($this->isEmpty()) {
            return new self($path);
        }

        $clone = clone $this;
        $clone->value = \rtrim(self::decode($path), '/').'/'.\ltrim($this->value, '/');

        if ('/' !== $clone->value && $this->isAbsolute()) {
            // if current path is absolute, then returned path must also be absolute
            $clone->value = '/'.\ltrim($clone->value, '/');
        }

        return $clone;
    }

    private static function decode(string $value): string
    {
        return \implode('/', \array_map('rawurldecode', \explode('/', $value)));
    }
}
