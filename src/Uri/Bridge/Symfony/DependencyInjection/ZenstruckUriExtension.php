<?php

namespace Zenstruck\Uri\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Bridge\Symfony\Attribute\Signed;
use Zenstruck\Uri\Bridge\Symfony\Controller\UriValueResolver;
use Zenstruck\Uri\Bridge\Symfony\CurrentRequestUriFactory;
use Zenstruck\Uri\Bridge\Symfony\EventListener\VerifySignedRouteSubscriber;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedRouteLoader;
use Zenstruck\Uri\Bridge\Symfony\SignedUriGenerator;
use Zenstruck\Uri\Bridge\Symfony\SignedUriVerifier;
use Zenstruck\Uri\Bridge\Symfony\Twig\SymfonyUriExtension;
use Zenstruck\Uri\Bridge\Twig\UriExtension;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckUriExtension extends ConfigurableExtension implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('zenstruck_uri');

        $builder->getRootNode()
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
                ->booleanNode('controller_value_resolver')
                    ->info(\sprintf('Enable injecting "%s" and "%s" objects into your controllers', Uri::class, SignedUri::class))
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $builder;
    }

    /**
     * @param array<string,mixed[]> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        return $this;
    }

    /**
     * @param array<string,mixed[]> $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register('.zenstruck_uri.uri_signer', UriSigner::class)
            ->addArgument($mergedConfig['secret'])
        ;
        $container->register('.zenstruck_uri.current_request_uri_factory', CurrentRequestUriFactory::class)
            ->addArgument(new Reference('request_stack'))
            ->addTag('twig.runtime')
        ;
        $container->register('.zenstruck_uri.twig_extension', UriExtension::class)
            ->addTag('twig.extension')
        ;
        $container->register('.zenstruck_uri.symfony_twig_extension', SymfonyUriExtension::class)
            ->addTag('twig.extension')
        ;
        $container->register(SignedUriGenerator::class)
            ->setArguments([new Reference('.zenstruck_uri.uri_signer'), new Reference('router')])
            ->addTag('twig.runtime')
        ;
        $container->register(SignedUriVerifier::class)
            ->setArguments([new Reference('.zenstruck_uri.uri_signer'), new Reference('.zenstruck_uri.current_request_uri_factory')])
        ;

        if ($mergedConfig['controller_value_resolver']) {
            $container->register('.zenstruck.uri_value_resolver', UriValueResolver::class)
                ->setArguments([
                    new ServiceLocatorArgument([
                        UriSigner::class => new Reference('.zenstruck_uri.uri_signer'),
                    ]),
                ])
                ->addTag('controller.argument_value_resolver')
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
                        UriSigner::class => new Reference('.zenstruck_uri.uri_signer'),
                    ]),
                ])
                ->addTag('kernel.event_subscriber')
            ;
        }
    }
}
