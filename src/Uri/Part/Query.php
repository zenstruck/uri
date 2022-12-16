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

use Zenstruck\Uri\Parameters;
use Zenstruck\Uri\Part;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Query extends Part
{
    private string $string;
    private Parameters $parameters;

    /**
     * @param string|array<string,mixed>|null $value
     */
    public function __construct(string|array|null $value)
    {
        if (\is_array($value)) {
            $this->parameters = new Parameters($value);

            return;
        }

        $this->string = \ltrim((string) $value, '?');
    }

    public function toString(): string
    {
        return \http_build_query($this->parameters()->all(), '', '&', \PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->parameters()->all();
    }

    public function has(string $param): bool
    {
        return $this->parameters()->has($param);
    }

    /**
     * @template T
     *
     * @param T|\Throwable $default
     *
     * @return T
     *
     * @throws \Throwable If passed as default and no match
     */
    public function get(string $param, mixed $default = null): mixed
    {
        return $this->parameters()->get($param, $default);
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getString(string $param, string|\Throwable $default = ''): string
    {
        return $this->parameters()->getString($param, $default);
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getBool(string $param, bool|\Throwable $default = false): bool
    {
        return $this->parameters()->getBool($param, $default);
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getInt(string $param, int|\Throwable $default = 0): int
    {
        return $this->parameters()->getInt($param, $default);
    }

    public function without(string ...$params): self
    {
        $clone = clone $this;
        $clone->parameters = $this->parameters()->without(...$params);

        return $clone;
    }

    public function only(string ...$params): self
    {
        $clone = clone $this;
        $clone->parameters = $this->parameters()->only(...$params);

        return $clone;
    }

    public function with(string $param, mixed $value): self
    {
        $clone = clone $this;
        $clone->parameters = $this->parameters()->with($param, $value);

        return $clone;
    }

    private function parameters(): Parameters
    {
        if (isset($this->parameters)) {
            return $this->parameters;
        }

        $array = [];

        // convert string to array
        \parse_str($this->string, $array);

        return $this->parameters = new Parameters($array); // @phpstan-ignore-line
    }
}
