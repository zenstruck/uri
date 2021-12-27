<?php

namespace Zenstruck\Uri\Tests\Bridge\Twig;

use Twig\Test\IntegrationTestCase;
use Zenstruck\Uri\Bridge\Twig\UriExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriExtensionTest extends IntegrationTestCase
{
    protected function getExtensions(): array
    {
        return [new UriExtension()];
    }

    protected function getFixturesDir(): string
    {
        return __DIR__.'/Fixtures/';
    }
}
