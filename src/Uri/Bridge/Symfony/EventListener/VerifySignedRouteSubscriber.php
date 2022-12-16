<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Bridge\Symfony\EventListener;

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedRouteLoader;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlVerifier;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class VerifySignedRouteSubscriber implements EventSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $statusCode = $request->attributes->get('_route_params', [])[SignedRouteLoader::DEFAULT_KEY] ?? false;

        if (false === $statusCode) {
            return;
        }

        try {
            $this->container->get(SignedUrlVerifier::class)->verify($request);
        } catch (VerificationFailed $e) {
            throw \is_int($statusCode) ? new HttpException($statusCode, $e::REASON, $e) : $e;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [ControllerEvent::class => 'onController'];
    }
}
