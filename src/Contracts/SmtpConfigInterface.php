<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Contracts;

/**
 * Interface SmtpConfigInterface
 * Provides the structure for accessing SMTP configuration settings.
 */
interface SmtpConfigInterface extends MailProviderConfigInterface
{
    public function getHostString(): string;

    public function getDomain(): string;

    public function useTls(): bool;

    public function getTimeout(): int;

    public function getUsername(): string;

    public function getPassword(): string;
}