# zenstruck/uri

Object-oriented wrapper/manipulator for `parse_url` with the following features:
* Read URI _parts_ as objects (`Scheme`, `Host`, `Path`, `Query`), each with their own
  set of features.
* Manipulate URI parts or build URI's using a fluent builder API.
* Mailto object to help with reading/manipulating `mailto:` URIs.
* Optional [twig](https://twig.symfony.com/) extension.

This library is meant as a wrapper for PHP's `parse_url` function only and does not
conform to any PSR or RFC. If you need this, [league/uri](https://uri.thephpleague.com/)
would be a better choice.

## Installation

```bash
composer require zenstruck/uri
```

## Parsing/Reading URIs

```php
use Zenstruck\Uri;

// wrap a uri (this URI will be used for many of the samples below)
$uri = Uri::new('https://username:password@example.com/some/dir/file.html?q=abc&flag=1#test');

// can also wrap an instance of Symfony\Component\HttpFoundation\Request
/** @var Symfony\Component\HttpFoundation\Request $request */
$uri = Uri::new($request);

// URIs are stringable
$uri->toString();
(string) $uri;

// check if absolute
$uri->isAbsolute(); // true
Uri::new('/some/path/only')->isAbsolute(); // false

// SCHEME
$uri->scheme()->toString(); // "https"
$uri->scheme()->equals('https'); // true
$uri->scheme()->in(['https', 'http']); // true

// scheme segments - ie some kind of dsn
Uri::new('postmark+smtp://id')->scheme()->segments('+'); // ["postmark", "smtp"]
Uri::new('postmark+smtp://id')->scheme()->segment(0, '+'); // "postmark"
Uri::new('postmark+smtp://id')->scheme()->contains('postmark', '+'); // true

// HOST
$uri->host()->toString(); // example.com
$uri->host()->segments(); // ["example", "com"]
$uri->host()->segment(0); // "example"
$uri->host()->tld(); // "com"

// USER/PASS
$uri->user(); // "username"
$uri->pass(); // "password"

Uri::new('http://foo%40bar.com:pass%23word@example.com')->user(); // foo@bar.com (urldecoded)
Uri::new('http://foo%40bar.com:pass%23word@example.com')->pass(); // pass#word (urldecoded)

// PORT
$uri->port(); // (null)
Uri::new('example.com:21')->port(); // 21

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
Uri::new('/some/dir/')->path()->rtrim(); // "/some/dir"
Uri::new('/some/dir')->path()->isAbsolute(); // true
Uri::new('some/dir')->path()->isAbsolute(); // false
Uri::new('/some/dir/..')->path()->absolute(); // "/some"
Uri::new('/..')->path()->absolute(); // (throws \RuntimeException - path outside of root)
Uri::new('/some/dir')->path()->prepend('pre/fix'); // "/pre/fix/some/dir"
Uri::new('/some/dir')->path()->append('suf/fix'); // "/some/dir/suf/fix"
Uri::new('/foo%20bar/baz')->path()->toString(); // "/foo bar/baz" (urldecoded)

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

Uri::new('http://example.com')->fragment(); // (null)
Uri::new('http://example.com#frag%20ment')->fragment(); // "frag ment" (urldecoded)
```

## Manipulating URIs

**NOTE:** `Zenstruck\Uri` is an immutable object so any manipulations results in a new
instance.

```php
use Zenstruck\Uri;

// URI used for the following examples
$uri = Uri::new('https://user:pass@example.com/path?q=abc&flag=1#test');

// SCHEME
$uri->withScheme('http')->toString(); // "http://user:pass@example.com/path?q=abc&flag=1#test"
$uri->withoutScheme()->toString(); // "//user:pass@example.com/path?q=abc&flag=1#test"

// HOST
$uri->withHost('localhost')->toString(); // "https://user:pass@localhost/path?q=abc&flag=1#test"
Uri::new('//example.com/path/to/file.txt')->withoutHost()->toString(); // "/path/to/file.txt"

// USER
$uri->withUser('foo@bar.com')->toString(); // "https://foo%40bar.com:pass@example.com/path?q=abc&flag=1#test" (urlencoded)
$uri->withoutUser()->toString(); // "https://example.com/path?q=abc&flag=1#test" // removes password as well

// PASSWORD
$uri->withPass('pass#word')->toString(); // "https://user:pass%23word@example.com/path?q=abc&flag=1#test" (urlencoded)
$uri->withoutPass()->toString(); // "https://user@example.com/path?q=abc&flag=1#test"

// PORT
$uri->withPort(555)->toString(); // "https://user:pass@example.com:555/path?q=abc&flag=1#test"
Uri::new('http://example.com:22')->withoutPort()->toString(); // "http://example.com"

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
Uri::new()
    ->withHost('example.com')
    ->withScheme('https')
    ->withPath('/path')
    // ...
    ->toString() // "https://example.com/path"
;
```

## `Mailto` URIs

**NOTE:** `Zenstruck\Uri\Mailto` is an immutable object so any manipulations results in a new
instance.

```php
use Zenstruck\Uri\Mailto;

// Build
$mailto = Mailto::new('kevin@example.com')
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

## Twig extension

A [twig](https://twig.symfony.com/) extension providing `uri` and `mailto` filters is included.

Manual activation:

```php
/* @var \Twig\Environment $twig */

$twig->addExtension(new \Zenstruck\Uri\Bridge\Twig\UriExtension());
```

Symfony full-stack activation:

```yaml
# config/packages/zenstruck_uri.yaml

Zenstruck\Uri\Bridge\Twig\UriExtension: ~

# If not using auto-configuration:
Zenstruck\Uri\Bridge\Twig\UriExtension:
    tag: twig.extension
```
