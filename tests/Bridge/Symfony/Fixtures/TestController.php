<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zenstruck\Uri;
use Zenstruck\Uri\Bridge\Symfony\Attribute\Signed;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestController
{
    /**
     * @Route("/verify-with-route-option", options={"signed": true})
     */
    public function verifyWithRouteOption(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/verify-with-route-option-and-custom-status-code", options={"signed": 404})
     */
    public function verifyWithRouteOptionAndCustomStatusCode(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/verify-with-route-attribute")
     */
    #[Signed]
    public function verifyWithRouteAttribute(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/verify-with-route-attribute-and-custom-status-code")
     */
    #[Signed(404)]
    public function verifyWithRouteAttributeAndCustomStatusCode(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/inject-uri")
     */
    public function injectUri(Uri $uri): Response
    {
        return new Response($uri->toString());
    }

    /**
     * @Route("/verify-with-injected-signed-uri")
     */
    public function verifyWithInjectedSignedUri(SignedUri $uri): Response
    {
        return new Response($uri->toString());
    }

    /**
     * @Route("/verify-with-injected-signed-uri-and-custom-status-code")
     */
    public function verifyWithInjectedSignedUriAndCustomStatusCode(
        #[Signed(404)]
        SignedUri $uri
    ): Response {
        return new Response($uri->toString());
    }
}
