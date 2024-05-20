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

use Charcoal\Mailer\Message\EndOfLine;

/**
 * Class ClientConfig
 * @package Charcoal\Mailer
 */
class ClientConfig
{
    public EndOfLine $eolChar;

    /**
     * @param string $name
     * @param string $boundary1Prefix
     * @param string $boundary2Prefix
     * @param string $boundary3Prefix
     */
    public function __construct(
        public string $name = "Charcoal Mailer " . Mailer::VERSION,
        public string $boundary1Prefix = "--Charcoal_B1",
        public string $boundary2Prefix = "--Charcoal_B2",
        public string $boundary3Prefix = "--Charcoal_B3",
    )
    {
        $this->eolChar = EndOfLine::from(PHP_EOL);
    }

    /**
     * @param string $uniqueId
     * @return array
     */
    public function getMimeBoundaries(string $uniqueId): array
    {
        $boundaries[] = $this->boundary1Prefix . $uniqueId;
        $boundaries[] = $this->boundary2Prefix . $uniqueId;
        $boundaries[] = $this->boundary3Prefix . $uniqueId;
        return $boundaries;
    }
}