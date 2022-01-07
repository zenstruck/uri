<?php

namespace Zenstruck;

use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Uri\Authority;
use Zenstruck\Uri\Host;
use Zenstruck\Uri\Path;
use Zenstruck\Uri\Query;
use Zenstruck\Uri\Scheme;
use Zenstruck\Uri\Stringable;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Uri
{
    use Stringable;

    private Scheme $scheme;
    private Authority $authority;
    private Path $path;
    private Query $query;
    private ?string $fragment;

    public function __construct(?string $value = null)
    {
        if (false === $components = \parse_url((string) $value)) {
            throw new \InvalidArgumentException("Unable to parse \"{$value}\".");
        }

        $this->scheme = new Scheme($components['scheme'] ?? '');
        $this->path = new Path($components['path'] ?? '');
        $this->query = new Query($components['query'] ?? []);
        $this->fragment = isset($components['fragment']) ? \rawurldecode($components['fragment']) : null;
        $this->authority = new Authority(
            $components['host'] ?? '',
            $components['user'] ?? null,
            $components['pass'] ?? null,
            $components['port'] ?? null
        );
    }

    /**
     * @param string|self|Request|null $value
     */
    public static function new($value = null): self
    {
        if ($value instanceof Request) {
            return self::new($value->getUri())
                ->withUser($value->getUser())
                ->withPass($value->getPassword())
            ;
        }

        return $value instanceof self ? $value : new self($value);
    }

    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    public function host(): Host
    {
        return $this->authority->host();
    }

    public function port(): ?int
    {
        return $this->authority->port();
    }

    public function user(): ?string
    {
        return $this->authority->username();
    }

    public function pass(): ?string
    {
        return $this->authority->password();
    }

    public function path(): Path
    {
        return $this->path;
    }

    public function query(): Query
    {
        return $this->query;
    }

    public function fragment(): ?string
    {
        return $this->fragment;
    }

    public function authority(): Authority
    {
        return $this->authority;
    }

    public function isAbsolute(): bool
    {
        return !$this->scheme->isEmpty();
    }

    /**
     * @return int|null The explicit port or the default for the scheme
     */
    public function guessPort(): ?int
    {
        return $this->port() ?? $this->scheme()->defaultPort();
    }

    public function withHost(?string $host): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withHost($host);

        return $uri;
    }

    public function withoutHost(): self
    {
        return $this->withHost(null);
    }

    public function withScheme(?string $scheme): self
    {
        $uri = clone $this;
        $uri->scheme = new Scheme((string) $scheme);

        return $uri;
    }

    public function withoutScheme(): self
    {
        return $this->withScheme(null);
    }

    public function withPort(?int $port): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withPort($port);

        return $uri;
    }

    public function withoutPort(): self
    {
        return $this->withPort(null);
    }

    public function withUser(?string $user): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withUsername($user);

        return $uri;
    }

    public function withoutUser(): self
    {
        return $this->withUser(null);
    }

    public function withPass(?string $pass): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withPassword($pass);

        return $uri;
    }

    public function withoutPass(): self
    {
        return $this->withPass(null);
    }

    public function withPath(?string $path): self
    {
        $uri = clone $this;
        $uri->path = new Path((string) $path);

        return $uri;
    }

    public function appendPath(string $path): self
    {
        return $this->withPath($this->path->append($path));
    }

    public function prependPath(string $path): self
    {
        return $this->withPath($this->path->prepend($path));
    }

    public function withoutPath(): self
    {
        return $this->withPath(null);
    }

    public function withQuery(?array $query): self
    {
        $uri = clone $this;
        $uri->query = new Query($query ?? []);

        return $uri;
    }

    /**
     * @param mixed $value
     */
    public function withQueryParam(string $param, $value): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withQueryParam($param, $value);

        return $uri;
    }

    public function withOnlyQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withOnlyQueryParams(...$params);

        return $uri;
    }

    public function withoutQuery(): self
    {
        return $this->withQuery(null);
    }

    public function withoutQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withoutQueryParams(...$params);

        return $uri;
    }

    public function withFragment(?string $fragment): self
    {
        $uri = clone $this;
        $uri->fragment = '' === (string) $fragment ? null : \ltrim($fragment, '#');

        return $uri;
    }

    public function withoutFragment(): self
    {
        return $this->withFragment(null);
    }

    protected function generateString(): string
    {
        $ret = '';

        if (!$this->scheme->isEmpty()) {
            $ret .= "{$this->scheme}:";
        }

        if (!$this->authority->isEmpty() || $this->scheme->equals('file')) {
            // The file scheme is special in that it requires the "//" prefix.
            // PHP stream functions do not work with "file:/myfile.txt".
            $ret .= "//{$this->authority}";
        }

        if (!$this->path->isEmpty() && !$this->path->isAbsolute() && !$this->host()->isEmpty()) {
            // if host is set and path is non-absolute, make path absolute
            $ret .= '/';
        }

        $ret .= $this->path->encoded();

        if (!$this->query->isEmpty()) {
            $ret .= "?{$this->query}";
        }

        if (null !== $this->fragment) {
            $ret .= '#'.\rawurlencode($this->fragment);
        }

        return $ret;
    }
}
