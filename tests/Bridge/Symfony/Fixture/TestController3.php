<?php

namespace Zenstruck\Uri\Tests\Bridge\Symfony\Fixture;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zenstruck\Uri\Bridge\Symfony\Routing\Signed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[Signed]
final class TestController3
{
    #[Route('/method1')]
    public function method1(): Response
    {
        return new Response('Success');
    }

    #[Signed(404)]
    #[Route('/method2')]
    public function method2(): Response
    {
        return new Response('Success');
    }
}
