<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Zenstruck\Uri\Bridge\Symfony\ZenstruckUriBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const SECRET = 'S3CRET';

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new ZenstruckUriBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->register(Service::class)->setAutowired(true)->setPublic(true);
        $c->register(TestController::class)->addTag('controller.service_arguments');
        $c->register(ClassAnnotationTestController::class)->addTag('controller.service_arguments');
        $c->register(ClassAttributeTestController::class)->addTag('controller.service_arguments');
        $c->register('logger', NullLogger::class);

        $c->loadFromExtension('framework', [
            'secret' => self::SECRET,
            'test' => true,
            'router' => ['utf8' => true],
        ]);
        $c->loadFromExtension('zenstruck_uri', [
            'route_verification' => true,
            'controller_value_resolver' => true,
        ]);
    }

    /**
     * @param RouteCollectionBuilder|RoutingConfigurator $routes
     */
    protected function configureRoutes($routes): void
    {
        $routes->import(__DIR__, 'annotation');
    }
}
