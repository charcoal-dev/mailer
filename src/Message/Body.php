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
 * Class Body
 * @package Charcoal\Mailer\Message
 */
class Body
{
    /**
     * @param string|null $html
     * @param string|null $plainText
     */
    public function __construct(public readonly ?string $html, public readonly ?string $plainText)
    {
    }
}