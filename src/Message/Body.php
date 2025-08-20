<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Message;

/**
 * Class Body
 * @package Charcoal\Mailer\Message
 */
readonly class Body
{
    public function __construct(
        public ?string $html,
        public ?string $plainText
    )
    {
    }
}