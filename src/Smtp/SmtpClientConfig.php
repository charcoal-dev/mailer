<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Smtp;

use Charcoal\Mailer\Contracts\MailProviderConfigInterface;

/**
 * Represents the configuration settings required for an SMTP mail provider.
 * This class is used to define parameters for connecting and authenticating with an SMTP server.
 */
final readonly class SmtpClientConfig implements MailProviderConfigInterface
{
    public function __construct(
        public string      $host,
        public int         $port,
        public string      $domain,
        public bool        $startTls = true,
        public string|null $username = null,
        public string|null $password = null,
        public int         $timeOut = 1
    )
    {
    }
}