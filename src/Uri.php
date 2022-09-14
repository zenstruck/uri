<?php

namespace Zenstruck;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Zenstruck\Uri\Authority;
use Zenstruck\Uri\Host;
use Zenstruck\Uri\Path;
use Zenstruck\Uri\Query;
use Zenstruck\Uri\Scheme;
use Zenstruck\Uri\Signed\Builder;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\UriAlreadyUsed;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;
use Zenstruck\Uri\SignedUri;
use Zenstruck\Uri\Stringable;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
class Uri implements \Stringable
{
    use Stringable;

    private Scheme $scheme;
    private Authority $authority;
    private Path $path;
    private Query $query;
    private ?string $fragment;

    /**
     * @param string|self|null $value
     */
    public function __construct($value = null)
    {
        if ($value instanceof self) {
            $this->createFromSelf($value);

            return;
        }

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
            return (new self($value->getUri()))
                ->withUser($value->getUser())
                ->withPass($value->getPassword())
            ;
        }

        return $value instanceof self && self::class === \get_class($value) ? $value : new self($value);
    }

    final public function scheme(): Scheme
    {
        return $this->scheme;
    }

    final public function host(): Host
    {
        return $this->authority->host();
    }

    final public function port(): ?int
    {
        return $this->authority->port();
    }

    final public function user(): ?string
    {
        return $this->authority->username();
    }

    final public function pass(): ?string
    {
        return $this->authority->password();
    }

    final public function path(): Path
    {
        return $this->path;
    }

    final public function query(): Query
    {
        return $this->query;
    }

    final public function fragment(): ?string
    {
        return $this->fragment;
    }

    final public function authority(): Authority
    {
        return $this->authority;
    }

    final public function isAbsolute(): bool
    {
        return !$this->scheme->isEmpty();
    }

    /**
     * @return int|null The explicit port or the default for the scheme
     */
    final public function guessPort(): ?int
    {
        return $this->port() ?? $this->scheme()->defaultPort();
    }

    /**
     * @return $this
     */
    final public function withHost(?string $host): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withHost($host);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutHost(): self
    {
        return $this->withHost(null);
    }

    /**
     * @return $this
     */
    final public function withScheme(?string $scheme): self
    {
        $uri = clone $this;
        $uri->scheme = new Scheme((string) $scheme);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutScheme(): self
    {
        return $this->withScheme(null);
    }

    /**
     * @return $this
     */
    final public function withPort(?int $port): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withPort($port);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutPort(): self
    {
        return $this->withPort(null);
    }

    /**
     * @return $this
     */
    final public function withUser(?string $user): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withUsername($user);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutUser(): self
    {
        return $this->withUser(null);
    }

    /**
     * @return $this
     */
    final public function withPass(?string $pass): self
    {
        $uri = clone $this;
        $uri->authority = $this->authority->withPassword($pass);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutPass(): self
    {
        return $this->withPass(null);
    }

    /**
     * @return $this
     */
    final public function withPath(?string $path): self
    {
        $uri = clone $this;
        $uri->path = new Path((string) $path);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function appendPath(string $path): self
    {
        return $this->withPath($this->path->append($path));
    }

    /**
     * @return $this
     */
    final public function prependPath(string $path): self
    {
        return $this->withPath($this->path->prepend($path));
    }

    /**
     * @return $this
     */
    final public function withoutPath(): self
    {
        return $this->withPath(null);
    }

    /**
     * @param mixed[]|null $query
     *
     * @return $this
     */
    final public function withQuery(?array $query): self
    {
        $uri = clone $this;
        $uri->query = new Query($query ?? []);

        return $uri;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    final public function withQueryParam(string $param, $value): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withQueryParam($param, $value);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withOnlyQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withOnlyQueryParams(...$params);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutQuery(): self
    {
        return $this->withQuery(null);
    }

    /**
     * @return $this
     */
    final public function withoutQueryParams(string ...$params): self
    {
        $uri = clone $this;
        $uri->query = $this->query->withoutQueryParams(...$params);

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withFragment(?string $fragment): self
    {
        $fragment = (string) $fragment;

        $uri = clone $this;
        $uri->fragment = '' === $fragment ? null : \ltrim($fragment, '#');

        return $uri;
    }

    /**
     * @return $this
     */
    final public function withoutFragment(): self
    {
        return $this->withFragment(null);
    }

    /**
     * @param string|UriSigner $secret
     */
    final public function sign($secret): Builder
    {
        return new Builder($this, $secret);
    }

    /**
     * @param string|UriSigner $secret
     * @param string|null      $singleUseToken If passed, this value MUST change once the URL is considered "used"
     *
     * @throws ExpiredUri       if the URI has expired
     * @throws UriAlreadyUsed   if the URI has already been used
     * @throws InvalidSignature if the URI could not be verified
     */
    final public function verify($secret, ?string $singleUseToken = null): SignedUri
    {
        return SignedUri::createVerified($this, $secret, $singleUseToken);
    }

    /**
     * @param string|UriSigner $secret
     * @param string|null      $singleUseToken If passed, this value MUST change once the URL is considered "used"
     */
    public function isVerified($secret, ?string $singleUseToken = null): bool
    {
        try {
            $this->verify($secret, $singleUseToken);

            return true;
        } catch (VerificationFailed $e) {
            return false;
        }
    }

    final protected function generateString(): string
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

    private function createFromSelf(self $value): void
    {
        $this->scheme = $value->scheme;
        $this->authority = $value->authority;
        $this->path = $value->path;
        $this->query = $value->query;
        $this->fragment = $value->fragment;

        if (isset($value->cachedString)) {
            $this->cachedString = $value->cachedString;
        }
    }
}
