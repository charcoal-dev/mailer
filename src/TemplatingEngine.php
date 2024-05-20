<?php
declare(strict_types=1);

namespace Charcoal\Mailer;

use Charcoal\Mailer\Exception\TemplatingException;
use Charcoal\Mailer\Templating\DataBindingTrait;
use Charcoal\Mailer\Templating\EmailBodyHtml;
use Charcoal\Mailer\Templating\EmailTemplateFile;
use Charcoal\Mailer\Templating\Modifiers;
use Charcoal\Mailer\Templating\RawTemplatedEmail;

/**
 * Class TemplatingEngine
 * @package Charcoal\Mailer
 */
class TemplatingEngine
{
    public readonly Modifiers $modifiers;
    public readonly string $pathToBodies;
    private array $templates = [];
    private array $bodies = [];

    use DataBindingTrait;

    /**
     * @param \Charcoal\Mailer\Mailer $mailer
     * @param string $pathToMessages Path to directory that contains all e-mail HTML bodies files
     */
    public function __construct(public readonly Mailer $mailer, string $pathToMessages)
    {
        $this->pathToBodies = rtrim($pathToMessages, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->modifiers = new Modifiers();
    }

    /**
     * @param string $filename Filename inside bodies directory WITHOUT ".html" suffix
     * @param bool $runtimeMemory
     * @return \Charcoal\Mailer\Templating\EmailBodyHtml
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function getBody(string $filename, bool $runtimeMemory = true): EmailBodyHtml
    {
        $filenameLc = strtolower($filename);
        if ($runtimeMemory && isset($this->bodies[$filenameLc])) {
            return $this->bodies[$filenameLc];
        }

        $body = EmailBodyHtml::FromHtmlFile($filename, $this->pathToBodies . $filename . ".html");
        $this->bodies[$filenameLc] = $body;
        return $body;
    }

    /**
     * @param \Charcoal\Mailer\Templating\EmailTemplateFile $template
     * @return $this
     */
    public function registerTemplate(EmailTemplateFile $template): static
    {
        $this->templates[strtolower($template->name)] = $template;
        return $this;
    }

    /**
     * @param string $template
     * @return \Charcoal\Mailer\Templating\EmailTemplateFile
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function getTemplate(string $template): EmailTemplateFile
    {
        $nameLc = strtolower($template);
        if (!isset($this->templates[$nameLc])) {
            throw new TemplatingException(sprintf('Template "%s" is not registered with mailer', $template));
        }

        return $this->templates[$nameLc];
    }

    /**
     * @param \Charcoal\Mailer\Templating\EmailTemplateFile|string $template
     * @param \Charcoal\Mailer\Templating\EmailBodyHtml|string $body
     * @param string $subject
     * @param string|null $preHeader
     * @return \Charcoal\Mailer\Templating\RawTemplatedEmail
     * @throws \Charcoal\Mailer\Exception\DataBindException
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function create(
        EmailTemplateFile|string $template,
        EmailBodyHtml|string     $body,
        string                   $subject,
        ?string                  $preHeader = null
    ): RawTemplatedEmail
    {
        if (is_string($template)) {
            $template = $this->getTemplate($template);
        }

        if (is_string($body)) {
            $body = $this->getBody($body, true);
        }

        return new RawTemplatedEmail($this, $template, $body, $subject, $preHeader);
    }
}