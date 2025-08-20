<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Exceptions;

/**
 * Class EmailComposeException
 * @package Charcoal\Mailer\Exceptions
 */
class EmailComposeException extends MailerException
{
    /**
     * @param string $key
     * @return static
     */
    public static function DisabledHeaderKey(string $key): static
    {
        return new static(sprintf('Cannot edit header value with key "%s"', $key));
    }

    /**
     * @param string $filePath
     * @return static
     */
    public static function AttachmentUnreadable(string $filePath): static
    {
        return new static(sprintf('Attachment filepath to "%s" is unreadable', basename($filePath)));
    }
}