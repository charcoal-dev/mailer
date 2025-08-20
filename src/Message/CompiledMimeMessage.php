<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Message;

/**
 * Class CompiledMimeMessage
 * @package Charcoal\Mailer\Message
 */
readonly class CompiledMimeMessage
{
    /**
     * @param string $subject
     * @param string $compiledMimeBody
     * @param Sender $sender
     */
    public function __construct(
        public string $subject,
        public string $compiledMimeBody,
        public Sender          $sender,
    )
    {
    }
}