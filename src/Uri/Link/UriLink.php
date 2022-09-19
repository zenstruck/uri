<?php

namespace Zenstruck\Uri\Link;

use Psr\Link\EvolvableLinkInterface;
use Psr\Link\LinkInterface;
use Zenstruck\Uri;
use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\WrappedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriLink extends WrappedUri implements EvolvableLinkInterface
{
    private Uri $uri;

    /** @var array<string,string> */
    private array $rels;

    /** @var array<string,scalar|mixed[]> */
    private array $attributes;

    /**
     * @param string[]|string|null         $rel
     * @param array<string,scalar|mixed[]> $attributes
     */
    public function __construct(Uri|string $href, array|string|null $rel = null, array $attributes = [])
    {
        $this->uri = ParsedUri::wrap($href);

        if (\is_string($rel)) {
            $rel = [$rel];
        }

        $this->rels = null !== $rel ? \array_combine($values = \array_values($rel), $values) : [];
        $this->attributes = $attributes;
    }

    public static function decode(string $encoded): self
    {
        if (!\preg_match('#<(.+)>#', $encoded, $matches)) {
            throw new \InvalidArgumentException(\sprintf('Unable to decode link "%s".', $encoded));
        }

        $href = new self($matches[1]);

        \preg_match_all('#(\w+)=\"([\w\s]+)\"#', $encoded, $matches, \PREG_SET_ORDER);
        $attributes = [];
        $rels = [];

        foreach ($matches as [, $key, $value]) {
            /** @var string $key */
            /** @var string $value */
            if ('rel' !== $key) {
                $attributes[$key] = $value;

                continue;
            }

            foreach (\array_unique(\explode(' ', $value)) as $rel) {
                $rels[] = $rel;
            }
        }

        return new self($href, $rels, $attributes);
    }

    public static function wrap(LinkInterface|string $link): self
    {
        if ($link instanceof self) {
            return $link;
        }

        if (\is_string($link)) {
            return new self($link);
        }

        $ret = new self($link->getHref());

        foreach ($link->getRels() as $rel) {
            $ret = $ret->withRel($rel);
        }

        foreach ($link->getAttributes() as $key => $value) {
            $ret = $ret->withAttribute($key, $value);
        }

        return $ret;
    }

    public function getHref(): string
    {
        return (string) $this;
    }

    public function isTemplated(): bool
    {
        return \str_contains($href = $this->getHref(), '{') || \str_contains($href, '}');
    }

    public function getRels(): array
    {
        return \array_keys($this->rels);
    }

    /**
     * @return array<string,scalar|mixed[]>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param \Stringable|string|Uri $href
     */
    public function withHref($href): static
    {
        $clone = clone $this;
        $clone->uri = ParsedUri::wrap($href);

        return $clone;
    }

    /**
     * @param string $rel
     */
    public function withRel($rel): static
    {
        $clone = clone $this;
        $clone->rels[$rel] = $rel;

        return $clone;
    }

    /**
     * @param string $rel
     */
    public function withoutRel($rel): static
    {
        $clone = clone $this;
        unset($clone->rels[$rel]);

        return $clone;
    }

    /**
     * @param string                                    $attribute
     * @param float|int|\Stringable|bool|mixed[]|string $value
     *
     * @return $this
     */
    public function withAttribute($attribute, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$attribute] = $value;

        return $clone;
    }

    /**
     * @param string $attribute
     */
    public function withoutAttribute($attribute): static
    {
        $clone = clone $this;
        unset($clone->attributes[$attribute]);

        return $clone;
    }

    protected function inner(): Uri
    {
        return $this->uri;
    }
}
