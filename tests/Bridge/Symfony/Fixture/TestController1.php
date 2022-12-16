<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixture;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Zenstruck\Uri\Bridge\Symfony\Routing\Signed;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlVerifier;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestController1
{
    #[Route('/verify-with-route-option', name: 'verify-with-route-option', options: ['signed' => true])]
    public function verifyWithRouteOption(): Response
    {
        return new Response('Success');
    }

    #[Route('/verify-with-route-option-and-custom-status-code', options: ['signed' => 404])]
    public function verifyWithRouteOptionAndCustomStatusCode(): Response
    {
        return new Response('Success');
    }

    #[Signed]
    #[Route('/verify-with-route-attribute')]
    public function verifyWithRouteAttribute(): Response
    {
        return new Response('Success');
    }

    #[Signed(404)]
    #[Route('/verify-with-route-attribute-and-custom-status-code')]
    public function verifyWithRouteAttributeAndCustomStatusCode(): Response
    {
        return new Response('Success');
    }

    #[Route('/verify', name: 'verify')]
    public function verify(SignedUrlVerifier $verifier): Response
    {
        $verifier->verifyCurrentRequest();

        return new Response('Success');
    }

    #[Route('/is-verify', name: 'is-verify')]
    public function isVerified(SignedUrlVerifier $verifier): Response
    {
        if ($verifier->isCurrentRequestVerified()) {
            return new Response('Success');
        }

        return new Response('Failure');
    }

    #[Route('/render/{template}')]
    public function render(string $template, Environment $twig): Response
    {
        return new Response($twig->render($template.'.html.twig'));
    }
}
