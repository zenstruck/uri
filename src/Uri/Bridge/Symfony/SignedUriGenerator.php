<?php

namespace Zenstruck\Uri\Bridge\Symfony;

use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Zenstruck\Uri;
use Zenstruck\Uri\Signed\Builder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUriGenerator implements UrlGeneratorInterface
{
    private UriSigner $signer;
    private UrlGeneratorInterface $inner;

    /**
     * @internal
     */
    public function __construct(UriSigner $signer, UrlGeneratorInterface $inner)
    {
        $this->signer = $signer;
        $this->inner = $inner;
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string
    {
        return $this->build($name, $parameters, $referenceType);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function build(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): Builder
    {
        return Uri::new($this->inner->generate($name, $parameters, $referenceType))->sign($this->signer);
    }

    public function setContext(RequestContext $context): void
    {
        $this->inner->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->inner->getContext();
    }
}
