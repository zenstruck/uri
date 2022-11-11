<?php

namespace Zenstruck\Uri\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Uri\Bridge\Symfony\EventListener\VerifySignedRouteSubscriber;
use Zenstruck\Uri\Bridge\Symfony\RequestUriFactory;
use Zenstruck\Uri\Bridge\Symfony\Routing\Signed;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedRouteLoader;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlVerifier;
use Zenstruck\Uri\Bridge\Symfony\Twig\SymfonyUriExtension;
use Zenstruck\Uri\Signed\SymfonySigner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckUriExtension extends ConfigurableExtension implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('zenstruck_uri');

        $builder->getRootNode() // @phpstan-ignore-line
            ->children()
                ->scalarNode('secret')
                    ->info('The secret key to sign/verify URIs with')
                    ->defaultValue('%kernel.secret%')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('route_verification')
                    ->info(\sprintf('Enable auto route verification (trigger with "%s" controller attribute or "signed" route option)', Signed::class))
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $builder;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface // @phpstan-ignore-line
    {
        return $this;
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void // @phpstan-ignore-line
    {
        $container->register('.zenstruck_uri.signer', SymfonySigner::class)
            ->addArgument($mergedConfig['secret'])
        ;
        $container->register('.zenstruck_uri.request_uri_factory', RequestUriFactory::class)
            ->addArgument(new Reference('request_stack'))
            ->addTag('twig.runtime')
        ;
        $container->register(SignedUrlGenerator::class)
            ->setArguments([new Reference('router'), new Reference('.zenstruck_uri.signer')])
            ->addTag('twig.runtime')
        ;
        $container->register(SignedUrlVerifier::class)
            ->setArguments([new Reference('.zenstruck_uri.signer'), new Reference('.zenstruck_uri.request_uri_factory')])
        ;

        if (isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            $container->register('.zenstruck_uri.twig_extension', SymfonyUriExtension::class)
                ->addTag('twig.extension')
            ;
        }

        if ($mergedConfig['route_verification']) {
            $container->register('.zenstruck_uri.signed_route_loader', SignedRouteLoader::class)
                ->setDecoratedService('routing.loader')
                ->setArguments([new Reference('.inner')])
            ;
            $container->register('.zenstruck_uri.verify_signed_route_subscriber', VerifySignedRouteSubscriber::class)
                ->setArguments([
                    new ServiceLocatorArgument([
                        SignedUrlVerifier::class => new Reference(SignedUrlVerifier::class),
                    ]),
                ])
                ->addTag('kernel.event_subscriber')
            ;
        }
    }
}
