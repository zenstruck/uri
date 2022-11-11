# zenstruck/uri

[![CI](https://github.com/zenstruck/uri/actions/workflows/ci.yml/badge.svg)](https://github.com/zenstruck/uri/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/zenstruck/uri/branch/2.x/graph/badge.svg?token=33QG3ZA3G0)](https://codecov.io/gh/zenstruck/uri)

Object-oriented wrapper/manipulator for `parse_url` with the following features:
* Read URI _parts_ as objects (`Scheme`, `Host`, `Path`, `Query`), each with their own
  set of features.
* Manipulate URI parts or build URI's using a fluent builder API.
* [Sign and verify](#signed-uris) URI's and make them temporary and/or single-use.
* [Mailto object](#mailto) to help with reading/manipulating `mailto:` URIs.
* [URI Template](#templateuri) ([RFC 6570](http://tools.ietf.org/html/rfc6570)) support.
* [PSR-13 Link](#urilink) implementation/bridge.
* [Twig Extension](#twig-extension).

This library is meant as a wrapper for PHP's `parse_url` function only and does not
conform to any URI-related PSR or RFC. If you need this,
[league/uri](https://uri.thephpleague.com/) would be a better choice.

## Installation

```bash
composer require zenstruck/uri
```

## Parsing/Reading URIs

```php
use Zenstruck\Uri\ParsedUri;

// wrap a uri (this URI will be used for many of the samples below)
$uri = ParsedUri::wrap('https://username:password@example.com/some/dir/file.html?q=abc&flag=1#test');

// can wrap an instance of \Symfony\Component\HttpFoundation\Request
$uri = ParsedUri::wrap($request);

// URIs are stringable
$uri->toString();
(string) $uri;

// check if absolute
$uri->isAbsolute(); // true
ParsedUri::wrap('/some/path/only')->isAbsolute(); // false

// SCHEME
$uri->scheme()->toString(); // "https"
$uri->scheme()->equals('https'); // true
$uri->scheme()->in(['https', 'http']); // true

// scheme segments - ie some kind of dsn (delimiter defaults to "+")
ParsedUri::wrap('postmark+smtp://id')->scheme()->segments(); // ["postmark", "smtp"]
ParsedUri::wrap('postmark+smtp://id')->scheme()->segment(0); // "postmark"
ParsedUri::wrap('postmark+smtp://id')->scheme()->segment(1); // "smtp"
ParsedUri::wrap('postmark+smtp://id')->scheme()->segment(2); // null
ParsedUri::wrap('postmark+smtp://id')->scheme()->segment(2, 'default'); // "default"
ParsedUri::wrap('postmark+smtp://id')->scheme()->contains('postmark'); // true

// customize the delimiter
ParsedUri::wrap('postmark-smtp://id')->scheme()->segments('-'); // ["postmark", "smtp"]
ParsedUri::wrap('postmark-smtp://id')->scheme()->segment(0, delimiter: '-'); // ["postmark", "smtp"]
ParsedUri::wrap('postmark-smtp://id')->scheme()->contains('postmark', delimiter: '-'); // true

// HOST
$uri->host()->toString(); // example.com
$uri->host()->segments(); // ["example", "com"]
$uri->host()->segment(0); // "example"
$uri->host()->tld(); // "com"

// USER/PASS
$uri->username(); // "username"
$uri->password(); // "password"

ParsedUri::wrap('http://foo%40bar.com:pass%23word@example.com')->username(); // foo@bar.com (urldecoded)
ParsedUri::wrap('http://foo%40bar.com:pass%23word@example.com')->password(); // pass#word (urldecoded)

// PORT
$uri->port(); // (null)
ParsedUri::wrap('example.com:21')->port(); // 21

// guess port from scheme
ParsedUri::wrap('http://example.com')->guessPort(); // 80
ParsedUri::wrap('http://example.com:555')->guessPort(); // 555 (returns explicitly set port if available)

// PATH
$uri->path()->toString(); // "/some/dir/file.html"
$uri->path()->segments(); // ["some", "dir", "file.html"]
$uri->path()->segment(0); // ["some"]
$uri->path()->trim(); // "some/dir/file.html"
$uri->path()->ltrim(); // "some/dir/file.html"
$uri->path()->dirname(); // "/some/dir"
$uri->path()->filename(); // "file"
$uri->path()->basename(); // "file.html"
$uri->path()->extension(); // "html"

// path helper methods
ParsedUri::wrap('/some/dir/')->path()->rtrim(); // "/some/dir"
ParsedUri::wrap('/some/dir')->path()->isAbsolute(); // true
ParsedUri::wrap('some/dir')->path()->isAbsolute(); // false
ParsedUri::wrap('/some/dir/..')->path()->absolute(); // "/some"
ParsedUri::wrap('/..')->path()->absolute(); // (throws \RuntimeException - path outside of root)
ParsedUri::wrap('/some/dir')->path()->prepend('pre/fix'); // "/pre/fix/some/dir"
ParsedUri::wrap('/some/dir')->path()->append('suf/fix'); // "/some/dir/suf/fix"
ParsedUri::wrap('/foo%20bar/baz')->path()->toString(); // "/foo bar/baz" (urldecoded)

// QUERY
$uri->query()->toString(); // "q=abc&flag=1"
$uri->query()->all(); // ["q" => "abc", "flag => "1"]
$uri->query()->has('q'); // true
$uri->query()->has('missing'); // false

$uri->query()->get('q'); // "abc"
$uri->query()->get('missing'); // (null)
$uri->query()->get('missing', 'default'); // "default"
$uri->query()->get('missing', new \Exception()); // (throws passed \Exception)

$uri->query()->getBool('flag'); // true
$uri->query()->getBool('missing'); // false
$uri->query()->getBool('missing', true); // true
$uri->query()->getBool('missing', new \Exception()); // (throws passed \Exception)

$uri->query()->getInt('flag'); // 1
$uri->query()->getInt('missing'); // 0
$uri->query()->getInt('missing', 5); // 5
$uri->query()->getInt('missing', new \Exception()); // (throws passed \Exception)

// FRAGMENT
$uri->fragment(); // "test"

ParsedUri::wrap('http://example.com')->fragment(); // (null)
ParsedUri::wrap('http://example.com#frag%20ment')->fragment(); // "frag ment" (urldecoded)
```

## Manipulating URIs

> **Note**: `Zenstruck\Uri\ParsedUri` is an immutable object so any manipulations results in a new
> instance.

```php
use Zenstruck\Uri\ParsedUri;

// URI used for the following examples
$uri = ParsedUri::wrap('https://user:pass@example.com/path?q=abc&flag=1#test');

// SCHEME
$uri->withScheme('http')->toString(); // "http://user:pass@example.com/path?q=abc&flag=1#test"
$uri->withoutScheme()->toString(); // "//user:pass@example.com/path?q=abc&flag=1#test"

// HOST
$uri->withHost('localhost')->toString(); // "https://user:pass@localhost/path?q=abc&flag=1#test"
$uri->withoutHost()->toString(); // "https:/path?q=abc&flag=1#test" (removes username/password/port as well)

// USER
$uri->withUsername('foo@bar.com')->toString(); // "https://foo%40bar.com:pass@example.com/path?q=abc&flag=1#test" (urlencoded)
$uri->withoutUsername()->toString(); // "https://example.com/path?q=abc&flag=1#test" (removes password as well)

// PASSWORD
$uri->withPassword('pass#word')->toString(); // "https://user:pass%23word@example.com/path?q=abc&flag=1#test" (urlencoded)
$uri->withoutPassword()->toString(); // "https://user@example.com/path?q=abc&flag=1#test"

// PORT
$uri->withPort(555)->toString(); // "https://user:pass@example.com:555/path?q=abc&flag=1#test"
ParsedUri::new('http://example.com:22')->withoutPort()->toString(); // "http://example.com"

// PATH
$uri->withPath('/replace')->toString(); // "https://user:pass@example.com/replace?q=abc&flag=1#test"
$uri->withoutPath()->toString(); // "https://user:pass@example.com?q=abc&flag=1#test"
$uri->prependPath('/prefix')->toString(); // "https://user:pass@example.com/prefix/path?q=abc&flag=1#test"
$uri->appendPath('/suffix')->toString(); // "https://user:pass@example.com/path/suffix?q=abc&flag=1#test"

// QUERY
$uri->withQuery(['foo' => 'bar'])->toString(); // "https://user:pass@example.com/path?foo=bar#test"
$uri->withQueryParam('foo', 'bar')->toString(); // "https://user:pass@example.com/path?q=abc&flag=1&foo=bar#test"
$uri->withoutQuery()->toString(); // "https://user:pass@example.com/path#test"
$uri->withoutQueryParams('q', 'missing')->toString(); // "https://user:pass@example.com/path?flag=1#test"
$uri->withOnlyQueryParams('q', 'missing')->toString(); // "https://user:pass@example.com/path?q=abc#test"

// FRAGMENT
$uri->withFragment('frag ment')->toString(); // "https://user:pass@example.com/path?q=abc&flag=1#frag%20ment" (urlencoded)
$uri->withoutFragment()->toString(); // "https://user:pass@example.com/path?q=abc&flag=1"

// URI Builder
ParsedUri::new()
    ->withHost('example.com')
    ->withScheme('https')
    ->withPath('/path')
    // ...
    ->toString() // "https://example.com/path"
;
```

## Signed URIs

> **Note**: `symfony/http-kernel` is required to sign and verify URIs `composer require symfony/http-kernel`.

You can sign a URI:

```php
$uri = Zenstruck\Uri\ParsedUri::wrap('https://example.com/some/path');

(string) $uri->sign('a secret'); // "https://example.com/some/path?_hash=..."
```

### Temporary URIs

Make an expiring signed URI:

```php
$uri = Zenstruck\Uri\ParsedUri::wrap('https://example.com/some/path');

(string) $uri->sign('a secret')->expires(new \DateTime('tomorrow')); // "https://example.com/some/path?_expires=...&_hash=..."

// # of seconds
(string) $uri->sign('a secret')->expires(3600); // "https://example.com/some/path?_expires=...&_hash=..."

// date string
(string) $uri->sign('a secret')->expires('+30 minutes'); // "https://example.com/some/path?_expires=...&_hash=..."
```

### Single-Use URIs

These URIs are generated with a token that should change _once the URI has been used_.

> **Note**: It is up to you to determine this token and depends on the context. This value **MUST** change
> after the token is successfully used, else it will still be valid.

```php
$uri = Zenstruck\Uri\ParsedUri::wrap('https://example.com/some/path');

(string) $uri->sign('a secret')->singleUse('some-token'); // "https://example.com/some/path?_token=...&_hash=..."
```

> **Note**: The URL is first hashed with this token, then hashed again with secret to ensure it hasn't
> been tampered with.

### Signed URI Builder

Calling `Zenstruck\Uri\ParsedUri::sign()` returns a `Zenstruck\Uri\Signed\Builder` object that can be used to
create single-use _and_ temporary URIs.

```php
$uri = Zenstruck\Uri\ParsedUri::wrap('https://example.com/some/path');

$builder = $uri->sign('a secret'); // Zenstruck\Uri\Signed\Builder

// create a single-use, temporary uri
$builder = $uri->sign('a secret')
    ->singleUse('some-token')
    ->expires('+30 minutes')
;

(string) $builder; // "https://example.com/some/path?_expires=...&_token=...&_hash=..."
```

> **Note**: `Zenstruck\Uri\Signed\Builder` is immutable objects so any manipulations results in a new instance.

### Verification

To verify a signed URI, create an instance of `Zenstruck\Uri\ParsedUri` and call `isVerified()` to
get true/false or `verify()` to throw specific exceptions:

```php
use Zenstruck\Uri\ParsedUri;
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\VerificationFailed;

$signedUri = ParsedUri::wrap('http://example.com/some/path?_hash=...');

$signedUri->isVerified('a secret'); // true/false

try {
    $signedUri->verify('a secret');
} catch (VerificationFailed $e) {
    $e::REASON; // ie "Invalid signature."
    $e->uri(); // \Zenstruck\Uri
}

// catch specific exceptions
try {
    $signedUri->verify('a secret');
} catch (InvalidSignature $e) {
    $e::REASON; // "Invalid signature."
    $e->uri(); // \Zenstruck\Uri
} catch (ExpiredUri $e) {
    $e::REASON; // "URI has expired."
    $e->uri(); // \Zenstruck\Uri
    $e->expiredAt(); // \DateTimeImmutable
}
```

#### Single-Use Verification

For validating [single-use URIs](#single-use-uris), you need to pass a token to the verify methods:

```php
use Zenstruck\Uri\Signed\Exception\InvalidSignature;
use Zenstruck\Uri\Signed\Exception\ExpiredUri;
use Zenstruck\Uri\Signed\Exception\UriAlreadyUsed;

/** @var \Zenstruck\Uri\ParsedUri $uri */

$uri->isVerified('a secret', 'some token'); // true/false

// catch specific exceptions
try {
    $uri->verify('a secret', 'some token');
} catch (InvalidSignature $e) {
    $e::REASON; // "Invalid signature."
    $e->uri(); // \Zenstruck\Uri
} catch (ExpiredUri $e) {
    $e::REASON; // "URI has expired."
    $e->uri(); // \Zenstruck\Uri
    $e->expiredAt(); // \DateTimeImmutable
} catch (UriAlreadyUsed $e) {
    $e::REASON; // "URI has already been used."
    $e->uri(); // \Zenstruck\Uri
}
```

### `SignedUri`

`Zenstruck\Uri\Signed\Builder::create()` and `Zenstruck\Uri\ParsedUri::verify()` both return a
`Zenstruck\Uri\SignedUri` object that implements `Zenstruck\Uri` and has some helpful methods.

> **Note**: `Zenstruck\Uri\SignedUri` is always considered verified and cannot be manipulated.

```php
$uri = Zenstruck\Uri\ParsedUri::wrap('https://example.com/some/path');

// create from the builder
$signedUri = $uri->sign('a secret')
    ->singleUse('a token')
    ->expires('tomorrow')
    ->create()
; // Zenstruck\Uri\SignedUri

// create from verify
$signedUri = $uri->verify('a secret'); // Zenstruck\Uri\SignedUri

$signedUri->isSingleUse(); // true
$signedUri->isTemporary(); // true
$signedUri->expiresAt(); // \DateTimeImmutable

// implements Zenstruck\Uri
$signedUri->query(); // Zenstruck\Uri\Query
```

## `UriLink`

A [PSR-13 Link](https://www.php-fig.org/psr/psr-13/) implementation is provided with:
* `Zenstruck\Uri\Link\UriLink` (implements both `Psr\Link\LinkInterface` and `Zenstruck\Uri`).
* `Zenstruck\Uri\Link\UriLinkProvider` (implements `Psr\Link\LinkProviderInterface` and
  provides `Zenstruck\Uri\Link\UriLink`'s).

## `TemplateUri`

> **Note**: `rize/uri-template` is required to use `TemplateUri` - `composer require rize/uri-template`.

`Zenstruck\Uri\TemplateUri` allows creating/manipulating [RFC 6570](http://tools.ietf.org/html/rfc6570)
uri templates and implements `Zenstruck\Uri`.

```php
use Zenstruck\Uri\TemplateUri;

// Expand
$uri = TemplateUri::expand('/repos/{owner}/{repo}', ['owner' => 'kbond', 'repo' => 'foundry']);

(string) $uri; // "/repos/kbond/foundry"
$uri->template(); // "/repos/{owner}/{repo}"
$uri->parameters()->all(); // ['owner' => 'kbond', 'repo' => 'foundry']

// Extract
$uri = TemplateUri::extract('/repos/{owner}/{repo}', '/repos/kbond/foundry');

(string) $uri; // "/repos/kbond/foundry"
$uri->template(); // "/repos/{owner}/{repo}"
$uri->parameters()->all(); // ['owner' => 'kbond', 'repo' => 'foundry']
```

## `Mailto`

> **Note**: `Zenstruck\Uri\Mailto` is an immutable object so any manipulations results in a new
> instance.

```php
use Zenstruck\Uri\Mailto;

// Build
$mailto = Mailto::wrap('kevin@example.com')
    ->addTo('jane@example.com', 'Jane')
    ->addCc('ryan@example.com')
    ->addBcc('wouter@example.com')
    ->withSubject('my subject')
    ->withBody('some body')
    ->toString() // "mailto:kevin%40example.com%2CJane%20%3Cjane%40example.com%3E?cc=ryan%40example.com&bcc=wouter%40example.com&subject=my%20subject&body=some%20body"
;

// Parse/Read
$mailto = Mailto::new('mailto:kevin%40example.com%2CJane%20%3Cjane%40example.com%3E?cc=ryan%40example.com&bcc=wouter%40example.com&subject=my%20subject&body=some%20body');

$mailto->to(); // ["kevin@example.com", "Jane <jane@example.com>"]
$mailto->cc(); // ["ryan@example.com"]
$mailto->bcc(); // ["wouter@example.com"]
$mailto->subject(); // "my subject"
$mailto->body(); // "my body"
```

## Twig Extension

A [twig](https://twig.symfony.com/) extension providing `uri`, `mailto` filters and
functions is included.

### Manual activation

```php
/* @var \Twig\Environment $twig */

$twig->addExtension(new \Zenstruck\Uri\Bridge\Twig\UriExtension());
```

### Symfony full-stack activation

```yaml
# config/packages/zenstruck_uri.yaml

Zenstruck\Uri\Bridge\Twig\UriExtension: ~

# If not using auto-configuration:
Zenstruck\Uri\Bridge\Twig\UriExtension:
    tag: twig.extension
```

### Usage

```twig
{# Filters: #}
{{ 'https://example.com'|uri.withPath('some/path').withQueryParam('q', 'term') }} {# https://example.com/some/path?q=term #}
{{ 'kevin@example.com'|mailto.withSubject('my subject') }} {# mailto:kevin%40example.com?subject=my%20subject #}

{# Functions: #}
{{ uri().withScheme('https').withHost('example.com') }} {# https://example.com #}
{{ mailto().withTo('kevin@example.com').withSubject('my subject') }} {# mailto:kevin%40example.com?subject=my%20subject #}
```
