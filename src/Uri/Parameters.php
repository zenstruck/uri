<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Parameters
{
    /**
     * @param array<string,mixed> $values
     */
    public function __construct(private array $values)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function has(string $param): bool
    {
        return \array_key_exists($param, $this->values);
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
        if ($default instanceof \Throwable && !$this->has($param)) {
            throw $default;
        }

        return $this->values[$param] ?? $default;
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getString(string $param, string|\Throwable $default = ''): string
    {
        return (string) $this->get($param, $default);
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getBool(string $param, bool|\Throwable $default = false): bool
    {
        return \filter_var($this->get($param, $default), \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @throws \Throwable If passed as default and no match
     */
    public function getInt(string $param, int|\Throwable $default = 0): int
    {
        return (int) $this->get($param, $default);
    }

    public function without(string ...$keys): self
    {
        $clone = clone $this;

        foreach ($keys as $key) {
            unset($clone->values[$key]);
        }

        return $clone;
    }

    public function only(string ...$keys): self
    {
        $clone = clone $this;

        foreach (\array_keys($clone->values) as $key) {
            if (!\in_array($key, $keys, true)) {
                unset($clone->values[$key]);
            }
        }

        return $clone;
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->values[$key] = $value;

        return $clone;
    }

    /**
     * @param array<string,mixed> ...$arrays
     */
    public function merge(array ...$arrays): self
    {
        $clone = clone $this;
        $clone->values = \array_merge($clone->values, ...$arrays);

        return $clone;
    }
}
