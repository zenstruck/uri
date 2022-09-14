<?php

namespace Zenstruck\Uri\Bridge\Symfony\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Zenstruck\Uri\Bridge\Symfony\Attribute\Signed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class SignedRouteLoader implements LoaderInterface
{
    public const DEFAULT_KEY = '_signed';
    private const OPTION_KEY = 'signed';

    private LoaderInterface $inner;

    public function __construct(LoaderInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @param mixed $resource
     */
    public function load($resource, ?string $type = null): RouteCollection
    {
        /** @var RouteCollection $routes */
        $routes = $this->inner->load($resource, $type);

        foreach ($routes as $route) {
            self::parseSignedAttribute($route);

            if ($signed = $route->getOption(self::OPTION_KEY)) {
                $route->addDefaults([self::DEFAULT_KEY => $signed]);
            }
        }

        return $routes;
    }

    public function supports($resource, ?string $type = null): bool
    {
        return $this->inner->supports($resource, $type);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->inner->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->inner->setResolver($resolver);
    }

    private static function parseSignedAttribute(Route $route): void
    {
        if (\PHP_VERSION_ID < 80000) {
            return;
        }

        try {
            $method = new \ReflectionMethod($route->getDefault('_controller'));
        } catch (\ReflectionException $e) {
            return;
        }

        $attribute = $method->getAttributes(Signed::class)[0] ?? $method->getDeclaringClass()->getAttributes(Signed::class)[0] ?? null;

        if ($attribute) {
            $route->addOptions([self::OPTION_KEY => $attribute->newInstance()->statusCode]);
        }
    }
}
