<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Query
{
    use Stringable;

    /** @var array|string */
    private $value;

    /**
     * @param array|string $value
     */
    public function __construct($value)
    {
        if (\is_string($value)) {
            $value = \ltrim($value, '?');
        }

        $this->value = $value;
    }

    public function all(): array
    {
        if (\is_array($this->value)) {
            return $this->value;
        }

        // convert string to array
        \parse_str($this->value, $this->value);

        return $this->value;
    }

    public function has(string $param): bool
    {
        return \array_key_exists($param, $this->all());
    }

    /**
     * @param mixed|\Throwable|null $default
     *
     * @return mixed
     *
     * @throws \Throwable If passed as default and no match
     */
    public function get(string $param, $default = null)
    {
        if ($default instanceof \Throwable && !$this->has($param)) {
            throw $default;
        }

        return $this->all()[$param] ?? $default;
    }

    /**
     * @param bool|\Throwable $default
     *
     * @throws \Throwable If passed as default and no match
     */
    public function getBool(string $param, $default = false): bool
    {
        return \filter_var($this->get($param, $default), \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param int|\Throwable $default
     *
     * @throws \Throwable If passed as default and no match
     */
    public function getInt(string $param, $default = 0): int
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

    /**
     * @param mixed $value
     */
    public function withQueryParam(string $param, $value): self
    {
        $array = $this->all();
        $array[$param] = $value;

        return new self($array);
    }

    protected function generateString(): string
    {
        return \http_build_query($this->all(), '', '&', \PHP_QUERY_RFC3986);
    }
}
