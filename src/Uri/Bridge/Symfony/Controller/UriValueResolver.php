<?php

namespace Zenstruck\Uri\Bridge\Symfony\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri;
use Zenstruck\Uri\Bridge\Symfony\Attribute\Signed;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;
use Zenstruck\Uri\SignedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class UriValueResolver implements ArgumentValueResolverInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return \in_array($argument->getType(), [Uri::class, SignedUri::class], true);
    }

    /**
     * @return Uri[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Uri::class === $argument->getType()) {
            return [Uri::new($request)];
        }

        if (SignedUri::class !== $argument->getType()) {
            return [];
        }

        try {
            return [Uri::new($request)->verify($this->container->get(UriSigner::class))];
        } catch (VerificationFailed $e) {
            $statusCode = 403;

            if ($attribute = $argument->getAttributes(Signed::class)[0] ?? null) {
                /** @var \ReflectionAttribute<Signed> $attribute */
                $statusCode = $attribute->newInstance()->statusCode;
            }

            throw new HttpException($statusCode, $e::REASON, $e);
        }
    }
}
