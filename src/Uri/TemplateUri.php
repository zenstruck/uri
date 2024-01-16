<?php

/*
 * This file is part of the zenstruck/uri package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Uri;

use Rize\UriTemplate;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class TemplateUri extends Uri\WrappedUri
{
    private UriTemplate $parser;
    private Uri $uri;
    private Uri\Parameters $parameters;

    private function __construct(private string $template)
    {
        if (!\class_exists(UriTemplate::class)) {
            throw new \LogicException(\sprintf('"rize/uri-template" is required to use "%s". Install with "composer require rize/uri-template".', self::class));
        }
    }

    /**
     * @param array<string,mixed>|Parameters $parameters
     */
    public static function expand(string $template, array|Uri\Parameters $parameters): self
    {
        if ($parameters instanceof Uri\Parameters) {
            $parameters = $parameters->all();
        }

        $ret = new self($template);
        $ret->parameters = self::filterParameters($parameters);

        return $ret;
    }

    public static function extract(string $template, string|Uri $uri): self
    {
        $ret = new self($template);
        $ret->uri = Uri\ParsedUri::wrap($uri);

        return $ret;
    }

    public function template(): string
    {
        return $this->template;
    }

    public function parameters(): Uri\Parameters
    {
        return $this->parameters ??= self::filterParameters($this->parser()->extract($this->template, $this->uri) ?? throw new \LogicException());
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function withParameters(array $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = self::filterParameters($parameters);
        unset($clone->uri);

        return $clone;
    }

    /**
     * @param scalar|mixed[] $value
     */
    public function withParameter(string $key, bool|string|float|int|array $value): self
    {
        return $this->withParameters($this->parameters()->merge([$key => $value])->all());
    }

    public function withoutParameters(string ...$keys): self
    {
        $parameters = $this->parameters()->all();

        foreach ($keys as $key) {
            unset($parameters[$key]);
        }

        return $this->withParameters($parameters);
    }

    /**
     * @param array<string,mixed> ...$arrays
     */
    public function mergeParameters(...$arrays): self
    {
        return $this->withParameters($this->parameters()->merge(...$arrays)->all());
    }

    protected function inner(): Uri
    {
        return $this->uri ??= Uri\ParsedUri::wrap($this->parser()->expand($this->template, $this->parameters->all()));
    }

    /**
     * @param mixed[] $values
     */
    private static function filterParameters(array $values): Uri\Parameters
    {
        return new Uri\Parameters(\array_filter($values, static fn($v) => '' !== $v && null !== $v));
    }

    private function parser(): UriTemplate
    {
        return $this->parser ??= new UriTemplate();
    }
}
