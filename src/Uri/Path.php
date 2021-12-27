<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Path extends Part
{
    private const DEFAULT_DELIMITER = '/';

    public function __construct(string $value)
    {
        parent::__construct(\implode('/', \array_map('rawurldecode', \explode('/', $value))));
    }

    /**
     * @return array The path exploded with $delimiter
     */
    public function segments(string $delimiter = self::DEFAULT_DELIMITER): array
    {
        return \array_values(\array_filter(\explode($delimiter, $this->trim())));
    }

    /**
     * @param int $index 0-based
     */
    public function segment(int $index, ?string $default = null, string $delimiter = self::DEFAULT_DELIMITER): ?string
    {
        return $this->segments($delimiter)[$index] ?? $default;
    }

    public function trim(): string
    {
        return \trim($this->toString(), '/');
    }

    public function rtrim(): string
    {
        return \rtrim($this->toString(), '/');
    }

    public function ltrim(): string
    {
        return \ltrim($this->toString(), '/');
    }

    /**
     * @throws \RuntimeException if path outside of root (ie /../)
     */
    public function absolute(): string
    {
        $path = \explode('/', $this->toString());
        $stack = [];

        foreach ($path as $segment) {
            $segment = \trim($segment);

            switch (true) {
                case '..' === $segment && empty($stack):
                    throw new \RuntimeException(\sprintf('Cannot resolve absolute path for "%s". It is outside of the root.', $this->toString()));
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
        $trailingSlash = \count($stack) && '/' === \mb_substr($this->toString(), -1) ? '/' : '';

        return '/'.\ltrim(\implode('/', $stack), '/').$trailingSlash;
    }

    public function extension(): ?string
    {
        return \pathinfo($this->toString(), \PATHINFO_EXTENSION) ?: null;
    }

    public function dirname(): string
    {
        return \pathinfo($this->absolute(), \PATHINFO_DIRNAME);
    }

    public function filename(): ?string
    {
        return \pathinfo($this->absolute(), \PATHINFO_FILENAME) ?: null;
    }

    public function basename(): ?string
    {
        return \pathinfo($this->absolute(), \PATHINFO_BASENAME) ?: null;
    }

    public function encoded(): string
    {
        return \implode('/', \array_map('rawurlencode', \explode('/', $this->toString())));
    }

    public function isAbsolute(): bool
    {
        return 0 === \mb_strpos($this->toString(), '/');
    }

    public function append(string $path): string
    {
        if ('' === $path) {
            return $this->toString();
        }

        if ($this->isEmpty()) {
            return $path;
        }

        return $this->rtrim().'/'.\ltrim($path, '/');
    }

    public function prepend(string $path): string
    {
        if ('' === $path) {
            return $this->toString();
        }

        if ($this->isEmpty()) {
            return $path;
        }

        $ret = \rtrim($path, '/').'/'.$this->ltrim();

        if ('/' !== $ret[0] && $this->isAbsolute()) {
            // if current path is absolute, then returned path must also be absolute
            $ret = "/{$ret}";
        }

        return $ret;
    }
}
