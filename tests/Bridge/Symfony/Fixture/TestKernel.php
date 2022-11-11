<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixture;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Uri\Bridge\Symfony\ZenstruckUriBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestKernel extends Kernel
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
        $c->register(TestController1::class)->addTag('controller.service_arguments');
        $c->register(TestController2::class)->addTag('controller.service_arguments');
        $c->register(TestController3::class)->addTag('controller.service_arguments');
        $c->register('logger', NullLogger::class);

        $c->loadFromExtension('framework', [
            'secret' => self::SECRET,
            'test' => true,
            'router' => ['utf8' => true],
        ]);
        $c->loadFromExtension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);
        $c->loadFromExtension('zenstruck_uri', [
            'route_verification' => true,
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__, 'annotation');
    }
}
