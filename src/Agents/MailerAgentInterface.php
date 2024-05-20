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