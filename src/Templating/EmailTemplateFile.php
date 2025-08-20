<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Templating;

use Charcoal\Mailer\Exceptions\TemplatingException;

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
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
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