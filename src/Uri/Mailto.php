<?php

namespace Zenstruck\Uri;

use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Mailto
{
    use Stringable;

    private Uri $uri;

    public function __construct(?string $value = null)
    {
        $uri = Uri::new($value);

        $this->uri = $uri
            ->withScheme('mailto')
            ->withPath($uri->path()->trim())
            ->withoutHost()
            ->withoutPort()
            ->withoutUser()
            ->withoutFragment()
            ->withOnlyQueryParams('subject', 'body', 'cc', 'bcc')
        ;
    }

    /**
     * @param string|self|null $value
     */
    public static function new($value = null): self
    {
        return $value instanceof self ? $value : new self(Uri::new($value));
    }

    /**
     * @return string[]
     */
    public function to(): array
    {
        return \array_values(\array_filter(\array_map('trim', $this->uri->path()->segments(','))));
    }

    /**
     * @return string[]
     */
    public function cc(): array
    {
        return \array_values(\array_filter(\array_map('trim', \explode(',', $this->uri->query()->get('cc', '')))));
    }

    /**
     * @return string[]
     */
    public function bcc(): array
    {
        return \array_values(\array_filter(\array_map('trim', \explode(',', $this->uri->query()->get('bcc', '')))));
    }

    public function subject(): ?string
    {
        return $this->uri->query()->get('subject');
    }

    public function body(): ?string
    {
        return $this->uri->query()->get('body');
    }

    public function withTo(string ...$to): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withPath(\implode(',', $to));

        return $mailto;
    }

    public function addTo(string $email, ?string $name = null): self
    {
        return $this->withTo(...\array_merge($this->to(), [self::createEmail($email, $name)]));
    }

    public function withoutTo(): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withoutPath();

        return $mailto;
    }

    public function withSubject(string $subject): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withQueryParam('subject', $subject);

        return $mailto;
    }

    public function withoutSubject(): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withoutQueryParams('subject');

        return $mailto;
    }

    public function withBody(string $body): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withQueryParam('body', $body);

        return $mailto;
    }

    public function withoutBody(): self
    {
        $mailto = clone $this;
        $mailto->uri = $this->uri->withoutQueryParams('body');

        return $mailto;
    }

    public function withCc(string ...$cc): self
    {
        $mailto = clone $this;

        if (empty($cc)) {
            $mailto->uri = $this->uri->withoutQueryParams('cc');

            return $mailto;
        }

        $mailto->uri = $this->uri->withQueryParam('cc', \implode(',', $cc));

        return $mailto;
    }

    public function addCc(string $email, ?string $name = null): self
    {
        return $this->withCc(...\array_merge($this->cc(), [self::createEmail($email, $name)]));
    }

    public function withoutCc(): self
    {
        return $this->withCc();
    }

    public function withBcc(string ...$bcc): self
    {
        $mailto = clone $this;

        if (empty($bcc)) {
            $mailto->uri = $this->uri->withoutQueryParams('bcc');

            return $mailto;
        }

        $mailto->uri = $this->uri->withQueryParam('bcc', \implode(',', $bcc));

        return $mailto;
    }

    public function addBcc(string $email, ?string $name = null): self
    {
        return $this->withBcc(...\array_merge($this->bcc(), [self::createEmail($email, $name)]));
    }

    public function withoutBcc(): self
    {
        return $this->withBcc();
    }

    protected function generateString(): string
    {
        return $this->uri;
    }

    private static function createEmail(string $email, ?string $name = null): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }
}
