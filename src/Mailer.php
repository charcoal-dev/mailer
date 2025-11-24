<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer;

use Charcoal\Mailer\Agents\Sendmail;
use Charcoal\Mailer\Contracts\MailProviderInterface;
use Charcoal\Mailer\Message\Body;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Message\Sender;

/**
 * Class Mailer
 * @package Charcoal\Mailer
 */
final class Mailer
{
    public const string VERSION = "0.2.0";

    public MailProviderInterface $agent;
    public ClientConfig $clientConfig;

    /**
     * @param Sender $sender
     * @param MailProviderInterface|null $agent
     */
    public function __construct(public Sender $sender, ?MailProviderInterface $agent = null)
    {
        $this->clientConfig = new ClientConfig();
        $this->agent = $agent ?? new Sendmail();
    }

    /**
     * @param string $subject
     * @param Body $body
     * @param Sender|null $sender
     * @return Message
     */
    public function compose(string $subject, Body $body, ?Sender $sender): Message
    {
        return new Message($this, $subject, $body, $sender);
    }

    /**
     * @param CompiledMimeMessage|Message $message
     * @param string ...$emails
     * @return int
     * @throws Exceptions\EmailComposeException
     * @api
     */
    public function send(CompiledMimeMessage|Message $message, string ...$emails): int
    {
        return $this->agent->send($message, $emails);
    }
}