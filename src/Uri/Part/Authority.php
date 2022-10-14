<?php

namespace Zenstruck\Uri\Part;

use Zenstruck\Uri\Part;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Authority extends Part
{
    private string $string;

    public function __construct(private Host $host, private ?string $username, private ?string $password, private ?int $port)
    {
    }

    public function __clone()
    {
        unset($this->string);
    }

    public function host(): Host
    {
        return $this->host;
    }

    /**
     * @return string|null "urldecoded"
     */
    public function username(): ?string
    {
        return null !== $this->username ? \rawurldecode($this->username) : null;
    }

    /**
     * @return string|null "urldecoded"
     */
    public function password(): ?string
    {
        return null !== $this->password ? \rawurldecode($this->password) : null;
    }

    public function port(): ?int
    {
        return $this->port;
    }

    public function userInfo(): ?string
    {
        if (null === $user = $this->username()) {
            return null;
        }

        $ret = \rawurlencode($user);

        if (null !== $password = $this->password()) {
            $ret .= ':'.\rawurlencode($password);
        }

        return $ret;
    }

    public function withHost(?string $host): self
    {
        $authority = clone $this;
        $authority->host = new Host($host);

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
        $authority->username = null !== $username ? \rawurlencode($username) : null;

        if (null === $username) {
            // cannot have a password without a username
            $authority->password = null;
        }

        return $authority;
    }

    public function withPassword(?string $password): self
    {
        if (null !== $password && null === $this->username) {
            throw new \LogicException('Cannot have a password without a username.');
        }

        $authority = clone $this;
        $authority->password = null !== $password ? \rawurlencode($password) : null;

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

    public function toString(): string
    {
        if (isset($this->string)) {
            return $this->string;
        }

        $ret = $this->userInfo();
        $ret = $ret ? "{$ret}@{$this->host}" : $this->host->toString();

        if (null !== $this->port) {
            $ret .= ":{$this->port}";
        }

        return $this->string = $ret;
    }
}
