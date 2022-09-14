<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixtures;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zenstruck\Uri\Bridge\Symfony\Attribute\Signed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @Route(path: "/class-attribute")
 */
#[Signed]
final class ClassAttributeTestController
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
     * @Route("/method3")
     */
    #[Signed(404)]
    public function method4(): Response
    {
        return new Response('Success');
    }
}
