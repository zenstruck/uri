<?php

namespace Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Authority implements \Stringable
{
    use Stringable;

    private Host $host;
    private ?string $username;
    private ?string $password;
    private ?int $port;

    public function __construct(string $host, ?string $username, ?string $password, ?int $port)
    {
        $this->host = new Host($host);
        $this->port = $port;
        $this->username = null !== $username ? \rawurldecode($username) : null;
        $this->password = null !== $password ? \rawurldecode($password) : null;
    }

    public function host(): Host
    {
        return $this->host;
    }

    public function username(): ?string
    {
        return $this->username;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    public function port(): ?int
    {
        return $this->port;
    }

    public function userInfo(): ?string
    {
        if (null === $user = $this->username) {
            return null;
        }

        $ret = \rawurlencode($user);

        if (null !== $this->password) {
            $ret .= ':'.\rawurlencode($this->password);
        }

        return $ret;
    }

    public function withHost(?string $host): self
    {
        $authority = clone $this;
        $authority->host = new Host((string) $host);

        if ($authority->host->isEmpty()) {
            $authority->username = null;
            $authority->password = null;
            $authority->port = null;
        }

        return $authority;
    }

    public function withUsername(?string $username): self
    {
        $authority = clone $this;
        $authority->username = $username;

        if (null === $username) {
            // cannot have a password without a username
            $authority->password = null;
        }

        return $authority;
    }

    public function withPassword(?string $password): self
    {
        if (null !== $password && null === $this->username) {
            throw new \InvalidArgumentException('Cannot have a password without a username.');
        }

        $authority = clone $this;
        $authority->password = $password;

        return $authority;
    }

    public function withPort(?int $port): self
    {
        if (null !== $port && ($port < 0 || 0xFFFF < $port)) {
            throw new \InvalidArgumentException("Invalid port: {$port}. Must be between 0 and 65535.");
        }

        $authority = clone $this;
        $authority->port = $port;

        return $authority;
    }

    protected function generateString(): string
    {
        $ret = $this->userInfo();
        $ret = $ret ? "{$ret}@{$this->host}" : (string) $this->host;

        if (null !== $this->port) {
            $ret .= ":{$this->port}";
        }

        return $ret;
    }
}
