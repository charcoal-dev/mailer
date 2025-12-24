<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Smtp;

use Charcoal\Mailer\Contracts\MailProviderConfigInterface;
use Charcoal\Mailer\Contracts\SmtpConfigInterface;

/**
 * Represents the configuration settings required for an SMTP mail provider.
 * This class is used to define parameters for connecting and authenticating with an SMTP server.
 * @api
 */
final readonly class SmtpClientConfig implements
    MailProviderConfigInterface,
    SmtpConfigInterface
{
    public function __construct(
        public string  $host,
        public int     $port,
        public string  $domain,
        public bool    $startTls = true,
        public ?string $username = null,
        public ?string $password = null,
        public int     $timeout = 1
    )
    {
    }

    /**
     * @return string
     */
    public function getHostString(): string
    {
        return $this->host . ":" . $this->port;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    public function useTls(): bool
    {
        return $this->startTls;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}