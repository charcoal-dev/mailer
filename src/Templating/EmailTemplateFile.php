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

namespace Charcoal\Mailer\Templating;

use Charcoal\Mailer\Exception\TemplatingException;

/**
 * Class EmailTemplateFile
 * @package Charcoal\Mailer\Templating
 */
class EmailTemplateFile
{
    public readonly string $html;

    use DataBindingTrait;

    /**
     * @param string $name
     * @param string $filePath
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function __construct(
        public readonly string $name,
        public readonly string $filePath
    )
    {
        if (!is_readable($this->filePath)) {
            throw new TemplatingException(sprintf('Template file "%s" is not readable', $this->name));
        }

        $html = file_get_contents($this->filePath);
        if (!$html) {
            throw new TemplatingException(sprintf('Failed to read template "%s" file', $this->name));
        }

        $this->html = $html;
    }
}