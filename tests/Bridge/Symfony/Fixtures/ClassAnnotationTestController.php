<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @Route(path: "/class-annotation", options={"signed": true})
 */
final class ClassAnnotationTestController
{
    /**
     * @Route("/method1")
     */
    public function method1(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/method2")
     */
    public function method2(): Response
    {
        return new Response('Success');
    }

    /**
     * @Route("/method3", options={"signed": 404})
     */
    public function method4(): Response
    {
        return new Response('Success');
    }
}
