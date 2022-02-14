<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Scheme extends LowercasePart
{
    private const DEFAULT_DELIMITER = '+';
    private const SCHEME_DEFAULT_PORT = [
        'ftp' => 21,
        'ftps' => 21,
        'sftp' => 22,
        'gopher' => 70,
        'http' => 80,
        'https' => 443,
        'ws' => 80,
        'wss' => 443,
    ];

    public function __construct(string $value)
    {
        if ('://' === \mb_substr($value, -3)) {
            $value = \mb_substr($value, 0, -3);
        }

        parent::__construct($value);
    }

    /**
     * @param non-empty-string $delimiter
     *
     * @return string[] The scheme exploded with $delimiter
     */
    public function segments(string $delimiter = self::DEFAULT_DELIMITER): array
    {
        return \array_filter(\explode($delimiter, $this->toString()));
    }

    /**
     * @param int              $index     0-based
     * @param non-empty-string $delimiter
     */
    public function segment(int $index, ?string $default = null, string $delimiter = self::DEFAULT_DELIMITER): ?string
    {
        return $this->segments($delimiter)[$index] ?? $default;
    }

    public function equals(string $value): bool
    {
        return $value === $this->toString();
    }

    /**
     * @param mixed[] $value
     */
    public function in(array $value): bool
    {
        return \in_array($this->toString(), $value, true);
    }

    /**
     * @param non-empty-string $delimiter
     */
    public function contains(string $value, string $delimiter = self::DEFAULT_DELIMITER): bool
    {
        return \in_array($value, $this->segments($delimiter), true);
    }

    public function defaultPort(): ?int
    {
        return self::SCHEME_DEFAULT_PORT[$this->toString()] ?? null;
    }
}
