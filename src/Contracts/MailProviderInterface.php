<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Contracts;

use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Interface for defining mail provider functionalities.
 */
interface MailProviderInterface
{
    /**
     * @param CompiledMimeMessage|Message $message
     * @param array $recipients
     * @return int
     */
    public function send(CompiledMimeMessage|Message $message, array $recipients): int;
}