<?php

namespace Zenstruck\Uri\Bridge\Symfony\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Zenstruck\Uri\Bridge\Symfony\CurrentRequestUriFactory;
use Zenstruck\Uri\Bridge\Symfony\SignedUriGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class SymfonyUriExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_uri', [CurrentRequestUriFactory::class, 'create']),
            new TwigFunction('signed_uri', [SignedUriGenerator::class, 'build']),
        ];
    }
}
