<?php

namespace Zenstruck\Uri\Link;

use Psr\Link\LinkInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Traversable;
use Zenstruck\Uri\Stringable;

if (!\class_exists(GenericLinkProvider::class)) {
    throw new \LogicException('symfony/web-link (4.4+) is required to use this class. Install with "composer require symfony/web-link".');
}

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<UriLink>
 */
final class UriLinks extends GenericLinkProvider implements \Stringable, \Countable, \IteratorAggregate
{
    use Stringable;

    public static function fromString(?string $serialized): self
    {
        if ('' === $serialized = \trim((string) $serialized)) {
            return new self();
        }

        return new self(\array_map([UriLink::class, 'fromString'], \explode(',', $serialized)));
    }

    public function firstForRel(string $rel): ?UriLink
    {
        return $this->getLinksByRel($rel)[0] ?? null;
    }

    /**
     * @return UriLink[]
     */
    public function getLinks(): array
    {
        return self::normalizeLinks(parent::getLinks());
    }

    /**
     * @param string $rel
     *
     * @return UriLink[]
     */
    public function getLinksByRel($rel): array
    {
        return self::normalizeLinks(parent::getLinksByRel($rel));
    }

    public function count(): int
    {
        return \count($this->getLinks());
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->getLinks());
    }

    protected function generateString(): string
    {
        return (string) (new HttpHeaderSerializer())->serialize($this->getLinks()); // @phpstan-ignore-line
    }

    /**
     * @param LinkInterface[] $links
     *
     * @return UriLink[]
     */
    private static function normalizeLinks(array $links): array
    {
        return \array_map([UriLink::class, 'wrap'], $links);
    }
}
