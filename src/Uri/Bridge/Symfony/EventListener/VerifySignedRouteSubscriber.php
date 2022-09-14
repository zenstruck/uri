<?php

namespace Zenstruck\Uri\Bridge\Symfony\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedRouteLoader;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class VerifySignedRouteSubscriber implements EventSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $statusCode = $request->attributes->get('_route_params', [])[SignedRouteLoader::DEFAULT_KEY] ?? false;

        if (false === $statusCode) {
            return;
        }

        try {
            Uri::new($request)->verify($this->container->get(UriSigner::class));

            return;
        } catch (VerificationFailed $e) {
            throw new HttpException(\is_int($statusCode) ? $statusCode : 403, $e::REASON, $e);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [ControllerEvent::class => 'onController'];
    }
}
