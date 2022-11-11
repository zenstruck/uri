<?php

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
