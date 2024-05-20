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
 * Class EmailComposeException
 * @package Charcoal\Mailer\Exception
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