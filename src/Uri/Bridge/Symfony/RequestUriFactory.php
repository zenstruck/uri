<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Bridge\Symfony;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Zenstruck\Uri\ParsedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class RequestUriFactory
{
    public function __construct(private RequestStack $requests)
    {
    }

    public function create(?Request $request = null): ParsedUri
    {
        $request ??= $this->requests->getCurrentRequest() ?? throw new \RuntimeException('Current request not available.');

        return ParsedUri::wrap($request);
    }
}
