<?php

namespace Zenstruck\Uri\Link;

use Psr\Link\LinkInterface;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;
use Zenstruck\Uri;
use Zenstruck\Uri\Stringable;

if (!\class_exists(Link::class)) {
    throw new \LogicException('symfony/web-link (4.4+) is required to use this class. Install with "composer require symfony/web-link".');
}

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UriLink extends Link implements \Stringable
{
    use Stringable;

    private Uri $uri;

    /**
     * @param Uri|string $uri
     */
    public function __construct($uri, ?string $rel = null)
    {
        parent::__construct($rel, (string) $uri);

        if ($uri instanceof Uri) {
            $this->uri = $uri;
        }
    }

    public function __clone()
    {
        // todo, once this library supports php8+ only, can override getHref() so only that clone unsets $this->uri
        unset($this->uri);
    }

    public static function fromString(string $serialized): self
    {
        if (!\preg_match('#<(.+)>#', $serialized, $matches)) {
            throw new \InvalidArgumentException(\sprintf('Unable to parse link "%s".', $serialized));
        }

        $link = new self($matches[1]);

        \preg_match_all('#(\w+)=\"([\w\s]+)\"#', $serialized, $matches, \PREG_SET_ORDER);

        foreach ($matches as [, $key, $value]) {
            $link = self::addAttribute($link, $key, $value);
        }

        return $link;
    }

    public static function wrap(LinkInterface $link): self
    {
        if ($link instanceof self) {
            return $link;
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

    public function uri(): Uri
    {
        return $this->uri ??= Uri::new($this->getHref());
    }

    protected function generateString(): string
    {
        return (string) (new HttpHeaderSerializer())->serialize([$this]); // @phpstan-ignore-line
    }

    private static function addAttribute(self $link, string $key, string $value): self
    {
        if ('rel' !== $key) {
            return $link->withAttribute($key, $value);
        }

        foreach (\array_unique(\explode(' ', $value)) as $rel) {
            $link = $link->withRel($rel);
        }

        return $link;
    }
}
