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
 * Represents a "normalized" scheme.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Scheme extends Part
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

    private string $value;

    public function __construct(?string $value)
    {
        $value = (string) $value;

        if ('://' === \mb_substr($value, -3)) {
            $value = \mb_substr($value, 0, -3);
        }

        $this->value = \mb_strtolower($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @param non-empty-string $delimiter
     *
     * @return string[] The scheme exploded with $delimiter
     */
    public function segments(string $delimiter = self::DEFAULT_DELIMITER): array
    {
        return \array_filter(\explode($delimiter, $this->value));
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
        return $value === $this->value;
    }

    /**
     * @param string[] $values
     */
    public function in(array $values): bool
    {
        return \in_array($this->value, $values, true);
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
        return self::SCHEME_DEFAULT_PORT[$this->value] ?? null;
    }
}
