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

namespace Charcoal\Mailer;

use Charcoal\Mailer\Agents\MailerAgentInterface;
use Charcoal\Mailer\Agents\Sendmail;
use Charcoal\Mailer\Message\Body;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Message\Sender;

/**
 * Class Mailer
 * @package Charcoal\Mailer
 */
class Mailer
{
    public const VERSION = "0.1.0";

    public MailerAgentInterface $agent;
    public ClientConfig $clientConfig;

    /**
     * @param \Charcoal\Mailer\Message\Sender $sender
     * @param \Charcoal\Mailer\Agents\MailerAgentInterface|null $agent
     */
    public function __construct(public Sender $sender, ?MailerAgentInterface $agent = null)
    {
        $this->clientConfig = new ClientConfig();
        $this->agent = $agent ?? new Sendmail();
    }

    /**
     * @param string $subject
     * @param \Charcoal\Mailer\Message\Body $body
     * @param \Charcoal\Mailer\Message\Sender|null $sender
     * @return \Charcoal\Mailer\Message
     */
    public function compose(string $subject, Body $body, ?Sender $sender): Message
    {
        return new Message($this, $subject, $body, $sender);
    }

    /**
     * @param \Charcoal\Mailer\Message\CompiledMimeMessage|\Charcoal\Mailer\Message $message
     * @param string ...$emails
     * @return int
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
     */
    public function send(CompiledMimeMessage|Message $message, string ...$emails): int
    {
        return $this->agent->send($message, $emails);
    }
}