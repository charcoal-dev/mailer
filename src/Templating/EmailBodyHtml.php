<?php
declare(strict_types=1);

namespace Charcoal\Mailer\Templating;

use Charcoal\Mailer\Exception\TemplatingException;

/**
 * Class EmailBodyHtml
 * @package Charcoal\Mailer\Templating
 */
class EmailBodyHtml
{
    /**
     * @param string $name
     * @param string $filePath
     * @return static
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public static function FromHtmlFile(string $name, string $filePath): static
    {
        if (!is_readable($filePath)) {
            throw new TemplatingException(sprintf('Body file "%s" is not readable', $name));
        }

        $html = file_get_contents($filePath);
        if (!$html) {
            throw new TemplatingException(sprintf('Failed to read e-mail body "%s" file', $name));
        }

        return new static($html);
    }

    /**
     * @param string $html
     */
    public function __construct(public readonly string $html)
    {
    }
}