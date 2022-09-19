<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Mailto implements \Stringable
{
    private ParsedUri $uri;

    public function __construct(string $value)
    {
        $this->uri = new ParsedUri($value);

        $this->uri = $this->uri
            ->withScheme('mailto')
            ->withPath($this->uri->path()->trim())
            ->withoutHost()
            ->withoutPort()
            ->withoutUsername()
            ->withoutFragment()
            ->withOnlyQueryParams('subject', 'body', 'cc', 'bcc')
        ;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function new(self|string|null $what = null): self
    {
        return $what instanceof self ? $what : new self((string) $what);
    }

    public static function wrap(self|string|null $what): self
    {
        return self::new($what);
    }

    public function toString(): string
    {
        return $this->uri;
    }

    /**
     * @return string[]
     */
    public function to(): array
    {
        return self::splitEmails($this->uri->path());
    }

    /**
     * @return string[]
     */
    public function cc(): array
    {
        return self::splitEmails($this->uri->query()->getString('cc'));
    }

    /**
     * @return string[]
     */
    public function bcc(): array
    {
        return self::splitEmails($this->uri->query()->getString('bcc'));
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

    /**
     * @return string[]
     */
    private static function splitEmails(string $value): array
    {
        return \array_values(\array_filter(\array_map('trim', \explode(',', $value))));
    }

    private static function createEmail(string $email, ?string $name): string
    {
        return $name ? "{$name} <{$email}>" : $email;
    }
}
