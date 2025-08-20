<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Message;

/**
 * Class Sender
 * @package Charcoal\Mailer\Message
 */
readonly class Sender
{
    /**
     * @param string $email
     * @param string|null $name
     */
    public function __construct(
        public string  $email,
        public ?string $name = null
    )
    {
    }
}