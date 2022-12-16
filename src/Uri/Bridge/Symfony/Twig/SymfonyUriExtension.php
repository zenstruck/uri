<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Bridge\Symfony\Twig;

use Twig\TwigFunction;
use Zenstruck\Uri\Bridge\Symfony\RequestUriFactory;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;
use Zenstruck\Uri\Bridge\Twig\UriExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SymfonyUriExtension extends UriExtension
{
    public function getFunctions(): array
    {
        return \array_merge(parent::getFunctions(), [
            new TwigFunction('signed_url', [SignedUrlGenerator::class, 'build']),
            new TwigFunction('current_url', [RequestUriFactory::class, 'create']),
        ]);
    }
}
