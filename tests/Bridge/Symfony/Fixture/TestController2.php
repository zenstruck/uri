<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixture;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[Route('/prefix', options: ['signed' => true])]
final class TestController2
{
    #[Route('/method1')]
    public function method1(): Response
    {
        return new Response('Success');
    }

    #[Route('/method2', options: ['signed' => 404])]
    public function method2(): Response
    {
        return new Response('Success');
    }

    #[Route('/method3', options: ['signed' => false])]
    public function method3(): Response
    {
        return new Response('Success');
    }
}
