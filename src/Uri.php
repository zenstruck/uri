<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck;

use Zenstruck\Uri\Part\Authority;
use Zenstruck\Uri\Part\Host;
use Zenstruck\Uri\Part\Path;
use Zenstruck\Uri\Part\Query;
use Zenstruck\Uri\Part\Scheme;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Uri extends \Stringable
{
    public function scheme(): Scheme;

    public function authority(): Authority;

    /**
     * @return string|null "urldecoded"
     */
    public function username(): ?string;

    /**
     * @return string|null "urldecoded"
     */
    public function password(): ?string;

    public function host(): Host;

    public function port(): ?int;

    public function path(): Path;

    public function query(): Query;

    /**
     * @return string|null "urldecoded"
     */
    public function fragment(): ?string;

    /**
     * @return int|null The explicit port or the default for the scheme
     */
    public function guessPort(): ?int;

    public function isAbsolute(): bool;

    public function toString(): string;
}
