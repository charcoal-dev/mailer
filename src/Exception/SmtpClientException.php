<?php
/*
 * This file is a part of "charcoal-dev/mailer" package.
 * https://github.com/charcoal-dev/mailer
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/mailer/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Exception;

/**
 * Class SmtpClientException
 * @package Charcoal\Mailer\Exception
 */
class SmtpClientException extends MailerException
{
    public const CONNECTION_ERROR = 0x1a;
    public const UNEXPECTED_RESPONSE = 0x1b;
    public const TLS_NOT_AVAILABLE = 0x1c;
    public const TLS_NEGOTIATE_FAIL = 0x1d;
    public const INVALID_RECIPIENT = 0x14e;
    public const AUTH_UNAVAILABLE = 0x1f;
    public const AUTH_FAILED = 0x20;
    public const EXCEEDS_MAX_SIZE = 0x21;
    public const TIMED_OUT = 0x22;

    /**
     * @return static
     */
    public static function TimedOut(): static
    {
        return new static('SMTP stream timed out', static::TIMED_OUT);
    }

    /**
     * @param int $num
     * @param string $error
     * @return static
     */
    public static function ConnectionError(int $num, string $error): static
    {
        return new static(sprintf('Connection Error: [%1$d] %2$s', $num, $error), static::CONNECTION_ERROR);
    }

    /**
     * @param string $command
     * @param int $expect
     * @param int $got
     * @return static
     */
    public static function UnexpectedResponse(string $command, int $expect, int $got): static
    {
        return new static(
            sprintf('Expected "%2$d" from "%1$s" command, got "%3$d"', $command, $expect, $got),
            static::UNEXPECTED_RESPONSE
        );
    }

    /**
     * @return static
     */
    public static function TlsNotAvailable(): static
    {
        return new static('TLS encryption is not available at remote SMTP server', static::TLS_NOT_AVAILABLE);
    }

    /**
     * @return static
     */
    public static function TlsNegotiateFailed(): static
    {
        return new static('TLS negotiation failed with remote SMTP server', static::TLS_NEGOTIATE_FAIL);
    }

    /**
     * @param string $error
     * @return static
     */
    public static function InvalidRecipient(string $error): static
    {
        return new static(
            sprintf('Failed to set a recipient on remote SMTP server, "%1$s"', $error),
            static::INVALID_RECIPIENT
        );
    }

    /**
     * @return static
     */
    public static function AuthUnavailable(): static
    {
        return new static('No supported authentication method available on remote SMTP server', static::AUTH_UNAVAILABLE);
    }

    /**
     * @param string $error
     * @return static
     */
    public static function AuthFailed(string $error): static
    {
        return new static(sprintf('Authentication error "%1$s"', $error), static::AUTH_FAILED);
    }

    /**
     * @param int $size
     * @param int $max
     * @return static
     */
    public static function ExceedsMaximumSize(int $size, int $max): static
    {
        return new static(
            sprintf('MIME (%1$d bytes) exceeds maximum size of %2$d', $size, $max),
            static::EXCEEDS_MAX_SIZE
        );
    }
}