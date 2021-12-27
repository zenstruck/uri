<?php

namespace Zenstruck\Uri\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Zenstruck\Uri;
use Zenstruck\Uri\Mailto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('uri', [Uri::class, 'new']),
            new TwigFilter('mailto', [Mailto::class, 'new']),
        ];
    }
}
