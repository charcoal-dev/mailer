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