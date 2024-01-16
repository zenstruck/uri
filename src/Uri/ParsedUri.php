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

use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Uri\Part\Authority;
use Zenstruck\Uri\Part\Host;
use Zenstruck\Uri\Part\Path;
use Zenstruck\Uri\Part\Query;
use Zenstruck\Uri\Part\Scheme;
use Zenstruck\Uri\Signed\Builder;
use Zenstruck\Uri\Signed\Exception\AlreadyUsed;
use Zenstruck\Uri\Signed\Exception\Expired;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;

/**
 * Wrapper for PHP's native "parse_url()".
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class ParsedUri extends BaseUri
{
    /**
     * @var array{
     *      scheme?:string,
     *      host?:string,
     *      port?:int,
     *      user?:string,
     *      pass?:string,
     *      path?:string,
     *      query?:string,
     *      fragment?:string,
     * }
     */
    private array $parsed;
    private Scheme $scheme;
    private Authority $authority;
    private Path $path;
    private Query $query;
    private string $fragment;

    public function __construct(private string $value)
    {
    }

    public function __clone(): void
    {
        $this->parse();

        unset($this->value);
    }

    public static function new(self|string|null $what = null): self
    {
        return $what instanceof self ? $what : new self((string) $what);
    }

    public static function wrap(self|string|null|Request $what): self
    {
        if ($what instanceof Request) {
            $qs = ($qs = $what->server->get('QUERY_STRING')) ? '?'.$qs : '';

            // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
            $what = $what->getSchemeAndHttpHost().$what->getBaseUrl().$what->getPathInfo().$qs;
        }

        return self::new($what);
    }

    public function normalize(): self
    {
        return clone $this;
    }

    public function toString(): string
    {
        if (isset($this->value)) {
            return $this->value;
        }

        $this->value = '';

        if (!$this->scheme()->isEmpty()) {
            $this->value .= "{$this->scheme()}:";
        }

        if (!$this->authority()->isEmpty() || $this->scheme()->equals('file')) {
            // The file scheme is special in that it requires the "//" prefix.
            // PHP stream functions do not work with "file:/myfile.txt".
            $this->value .= "//{$this->authority()}";
        }

        if (!$this->path()->isEmpty() && !$this->path()->isAbsolute() && !$this->host()->isEmpty()) {
            // if host is set and path is non-absolute, make path absolute
            $this->value .= '/';
        }

        $this->value .= $this->path()->encode();

        if (!$this->query()->isEmpty()) {
            $this->value .= "?{$this->query()}";
        }

        if (null !== $this->fragment()) {
            $this->value .= '#'.\rawurlencode($this->fragment());
        }

        return $this->value;
    }

    public function scheme(): Scheme
    {
        return $this->scheme ??= new Scheme($this->parse()->parsed['scheme'] ?? null);
    }

    public function authority(): Authority
    {
        return $this->authority ??= new Authority(
            new Host($this->parse()->parsed['host'] ?? null),
            $this->parsed['user'] ?? null,
            $this->parsed['pass'] ?? null,
            $this->parsed['port'] ?? null,
        );
    }

    public function path(): Path
    {
        return $this->path ??= new Path($this->parse()->parsed['path'] ?? null);
    }

    public function query(): Query
    {
        return $this->query ??= new Query($this->parse()->parsed['query'] ?? null);
    }

    public function fragment(): ?string
    {
        $this->fragment ??= \rawurldecode($this->parse()->parsed['fragment'] ?? '');

        return '' === $this->fragment ? null : $this->fragment;
    }

    public function withHost(?string $host): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority()->withHost($host);

        return $uri;
    }

    public function withoutHost(): self
    {
        return $this->withHost(null);
    }

    public function withScheme(?string $scheme): self
    {
        $uri = clone $this;
        $uri->scheme = new Scheme($scheme);

        return $uri;
    }

    public function withoutScheme(): self
    {
        return $this->withScheme(null);
    }

    public function withPort(?int $port): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority()->withPort($port);

        return $uri;
    }

    public function withoutPort(): self
    {
        return $this->withPort(null);
    }

    public function withUsername(?string $username): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority()->withUsername($username);

        return $uri;
    }

    public function withoutUsername(): self
    {
        return $this->withUsername(null);
    }

    public function withPassword(?string $password): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority()->withPassword($password);

        return $uri;
    }

    public function withoutPassword(): self
    {
        return $this->withPassword(null);
    }

    public function withPath(?string $path): self
    {
        $uri = clone $this;
        $uri->path = new Path($path);

        return $uri;
    }

    public function withoutPath(): self
    {
        return $this->withPath(null);
    }

    public function appendPath(string $path): self
    {
        return $this->withPath($this->path()->append($path));
    }

    public function prependPath(string $path): self
    {
        return $this->withPath($this->path()->prepend($path));
    }

    /**
     * @param string|array<string,mixed>|null $query
     */
    public function withQuery(array|string|null $query): self
    {
        $uri = clone $this;
        $uri->query = new Query($query ?? []);

        return $uri;
    }

    public function withoutQuery(): self
    {
        return $this->withQuery(null);
    }

    public function withQueryParam(string $param, mixed $value): self
    {
        $uri = clone $this;
        $uri->query = $this->query()->with($param, $value);

        return $uri;
    }

    public function withOnlyQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query()->only(...$params);

        return $uri;
    }

    public function withoutQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query()->without(...$params);

        return $uri;
    }

    public function withFragment(?string $fragment): self
    {
        $uri = clone $this;
        $uri->fragment = \rawurldecode(\ltrim((string) $fragment, '#'));

        return $uri;
    }

    public function withoutFragment(): self
    {
        return $this->withFragment(null);
    }

    public function sign(string $secret): Builder
    {
        return new Builder($this, $secret);
    }

    /**
     * @param string|null $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *
     * @throws Expired          if the URI has expired
     * @throws AlreadyUsed      if the URI has already been used
     * @throws InvalidSignature if the URI could not be verified
     */
    public function verify(string $secret, ?string $singleUseToken = null): SignedUri
    {
        return SignedUri::verify($this, $secret, $singleUseToken);
    }

    public function isVerified(string $secret, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($secret, $singleUseToken);

            return true;
        } catch (VerificationFailed) {
            return false;
        }
    }

    private function parse(): self
    {
        if (isset($this->parsed)) {
            return $this;
        }

        if (false === $parsed = \parse_url($this->value)) {
            throw new \InvalidArgumentException("Unable to parse \"{$this->value}\".");
        }

        $this->parsed = $parsed;

        return $this;
    }
}
