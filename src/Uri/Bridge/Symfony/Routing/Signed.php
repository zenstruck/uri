<?php

namespace Zenstruck\Uri\Bridge\Symfony\Routing;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class Signed
{
    public function __construct(public ?int $statusCode = null)
    {
    }
}
