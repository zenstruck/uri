<?php

namespace Zenstruck\Uri\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Zenstruck\Uri\Mailto;
use Zenstruck\Uri\ParsedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('uri', [ParsedUri::class, 'new']),
            new TwigFunction('mailto', [Mailto::class, 'new']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('uri', [ParsedUri::class, 'new']),
            new TwigFilter('mailto', [Mailto::class, 'new']),
        ];
    }
}
