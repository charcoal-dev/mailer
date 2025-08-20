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

namespace Charcoal\Mailer\Agents;

use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class Sendmail
 * @package Charcoal\Mailer\Agents
 */
class Sendmail implements MailerAgentInterface
{
    /**
     * @param \Charcoal\Mailer\Message|\Charcoal\Mailer\Message\CompiledMimeMessage $message
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