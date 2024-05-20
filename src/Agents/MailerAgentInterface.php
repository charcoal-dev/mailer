<?php
declare(strict_types=1);

namespace Charcoal\Mailer\Agents;

use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Interface MailerAgentInterface
 * @package Charcoal\Mailer\Agents
 */
interface MailerAgentInterface
{
    /**
     * @param \Charcoal\Mailer\Message\CompiledMimeMessage|\Charcoal\Mailer\Message $message
     * @param array $recipients
     * @return int
     */
    public function send(CompiledMimeMessage|Message $message, array $recipients): int;
}