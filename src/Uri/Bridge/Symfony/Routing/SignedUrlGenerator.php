<?php

namespace Zenstruck\Uri\Bridge\Symfony\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\Signed\Builder;
use Zenstruck\Uri\Signed\SymfonySigner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SignedUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @internal
     */
    public function __construct(private UrlGeneratorInterface $inner, private SymfonySigner $signer)
    {
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string // @phpstan-ignore-line
    {
        return $this->build($name, $parameters, $referenceType);
    }

    public function temporary(\DateTimeInterface|\DateInterval|string|int $expires, string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): string // @phpstan-ignore-line
    {
        return $this->build($name, $parameters, $referenceType)->expires($expires);
    }

    public function build(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_URL): Builder // @phpstan-ignore-line
    {
        return new Builder(ParsedUri::wrap($this->inner->generate($name, $parameters, $referenceType)), $this->signer);
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
