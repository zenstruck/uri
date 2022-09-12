<?php

namespace Zenstruck\Uri\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Zenstruck\Uri;
use Zenstruck\Uri\Mailto;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('uri', [Uri::class, 'new']),
            new TwigFunction('mailto', [Mailto::class, 'new']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('uri', [Uri::class, 'new']),
            new TwigFilter('mailto', [Mailto::class, 'new']),
        ];
    }
}
