<?php

namespace Zenstruck\Uri;

use Rize\UriTemplate;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class TemplateUri extends WrappedUri
{
    private UriTemplate $parser;
    private Uri $uri;

    /** @var mixed[] */
    private array $parameters;

    private function __construct(private string $template)
    {
        if (!\class_exists(UriTemplate::class)) {
            throw new \LogicException(\sprintf('"rize/uri-template" is required to use "%s". Install with "composer require rize/uri-template".', self::class));
        }
    }

    /**
     * @param mixed[] $parameters
     */
    public static function expand(string $template, array $parameters): self
    {
        $ret = new self($template);
        $ret->parameters = self::filterParameters($parameters);

        return $ret;
    }

    public static function extract(string $template, string|Uri $uri): self
    {
        $ret = new self($template);
        $ret->uri = ParsedUri::wrap($uri);

        return $ret;
    }

    public function template(): string
    {
        return $this->template;
    }

    /**
     * @return mixed[]
     */
    public function parameters(): array
    {
        return $this->parameters ??= self::filterParameters($this->parser()->extract($this->template, $this->uri) ?? throw new \LogicException());
    }

    /**
     * @param mixed[] $parameters
     */
    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;
        unset($clone->uri);

        return $clone;
    }

    /**
     * @param scalar|mixed[] $value
     */
    public function withParameter(string $key, bool|string|float|int|array $value): self
    {
        return $this->withParameters(\array_merge($this->parameters(), [$key => $value]));
    }

    public function withoutParameters(string ...$keys): self
    {
        $parameters = $this->parameters();

        foreach ($keys as $key) {
            unset($parameters[$key]);
        }

        return $this->withParameters($parameters);
    }

    protected function inner(): Uri
    {
        return $this->uri ??= ParsedUri::wrap($this->parser()->expand($this->template, $this->parameters));
    }

    /**
     * @param mixed[] $values
     *
     * @return mixed[]
     */
    private static function filterParameters(array $values): array
    {
        return \array_filter($values, static fn($v) => '' !== $v && null !== $v);
    }

    private function parser(): UriTemplate
    {
        return $this->parser ??= new UriTemplate();
    }
}
