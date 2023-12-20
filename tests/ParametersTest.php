<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Tests;

use PHPUnit\Framework\TestCase;
use Zenstruck\Uri\Parameters;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ParametersTest extends TestCase
{
    /**
     * @test
     */
    public function get_string_on_other_types(): void
    {
        $this->assertSame('', (new Parameters(['foo' => ['bar']]))->getString('foo'));
        $this->assertSame('', (new Parameters(['foo' => null]))->getString('foo'));

        $this->expectException(\RuntimeException::class);

        (new Parameters(['foo' => []]))->getString('foo', new \RuntimeException());
    }

    /**
     * @test
     */
    public function get_int_on_other_types(): void
    {
        $this->assertSame(1, (new Parameters(['foo' => ['bar']]))->getInt('foo'));
        $this->assertSame(0, (new Parameters(['foo' => null]))->getInt('foo'));
    }
}
