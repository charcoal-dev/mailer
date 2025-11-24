<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Agents;

use Charcoal\Mailer\Contracts\MailProviderInterface;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class Sendmail
 * @package Charcoal\Mailer\Agents
 */
final class Sendmail implements MailProviderInterface
{
    /**
     * @param Message|CompiledMimeMessage $message
     * @param array $recipients
     * @return int
     * @throws \Charcoal\Mailer\Exceptions\EmailComposeException
     */
    public function send(Message|CompiledMimeMessage $message, array $recipients): int
    {
        if ($message instanceof Message) {
            $message = $message->compile();
        }

        $separator = sprintf('--MIME-SEPARATOR-%1$s', microtime(false));
        $messageMime = explode($separator, $message->compiled);
        $sendMail = mail(implode(",", $recipients), $message->subject, $messageMime[1], $messageMime[0]);
        return $sendMail ? count($recipients) : 0;
    }
}