<?php

namespace Zenstruck\Uri\Part;

use Zenstruck\Uri\Part;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Query extends Part
{
    private string $string;

    /** @var array<string,mixed> */
    private array $array;

    /**
     * @param string|array<string,mixed>|null $value
     */
    public function __construct(string|array|null $value)
    {
        if (\is_array($value)) {
            $this->array = $value;

            return;
        }

        $this->string = \ltrim((string) $value, '?');
    }

    public function toString(): string
    {
        return \http_build_query($this->all(), '', '&', \PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        if (isset($this->array)) {
            return $this->array;
        }

        $this->array = [];

        // convert string to array
        \parse_str($this->string, $this->array);

        return $this->array;
    }

    public function has(string $param): bool
    {
        return \array_key_exists($param, $this->all());
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

        return $this->all()[$param] ?? $default;
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

    public function withoutQueryParams(string ...$params): self
    {
        $array = $this->all();

        foreach ($params as $key) {
            unset($array[$key]);
        }

        return new self($array);
    }

    public function withOnlyQueryParams(string ...$params): self
    {
        $array = $this->all();

        foreach (\array_keys($array) as $param) {
            if (!\in_array($param, $params, true)) {
                unset($array[$param]);
            }
        }

        return new self($array);
    }

    public function withQueryParam(string $param, mixed $value): self
    {
        $array = $this->all();
        $array[$param] = $value;

        return new self($array);
    }
}
