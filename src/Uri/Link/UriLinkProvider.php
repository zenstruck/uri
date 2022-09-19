<?php

namespace Zenstruck\Uri\Link;

use Psr\Link\EvolvableLinkProviderInterface;
use Psr\Link\LinkInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<UriLink>
 */
final class UriLinkProvider implements EvolvableLinkProviderInterface, \Countable, \IteratorAggregate
{
    /** @var array<int,UriLink> */
    private array $links = [];

    /**
     * @param string[]|LinkInterface[] $links
     */
    public function __construct(array $links = [])
    {
        foreach ($links as $link) {
            $this->links[\spl_object_id($link = UriLink::wrap($link))] = $link;
        }
    }

    public static function decode(?string $encoded): self
    {
        if ('' === $encoded = \trim((string) $encoded)) {
            return new self();
        }

        return new self(\array_map([UriLink::class, 'decode'], \explode(',', $encoded)));
    }

    public function firstForRel(string $rel): ?UriLink
    {
        return $this->getLinksByRel($rel)[0] ?? null;
    }

    /**
     * @return UriLink[]
     */
    public function getLinks(): iterable
    {
        return \array_values($this->links);
    }

    /**
     * @param string $rel
     *
     * @return UriLink[]
     */
    public function getLinksByRel($rel): iterable
    {
        return \array_values(
            \array_filter($this->links, static fn(UriLink $l) => \in_array($rel, $l->getRels(), true))
        );
    }

    public function withLink(LinkInterface $link): static
    {
        $clone = clone $this;
        $clone->links[\spl_object_id($link = UriLink::wrap($link))] = $link;

        return $clone;
    }

    public function withoutLink(LinkInterface $link): static
    {
        $clone = clone $this;
        unset($clone->links[\spl_object_id($link)]);

        return $clone;
    }

    public function count(): int
    {
        return \count($this->links);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(\array_values($this->links));
    }
}
