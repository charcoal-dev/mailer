<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Templating;

use Charcoal\Mailer\Exceptions\TemplatingException;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\Body;
use Charcoal\Mailer\Message\Sender;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Class RawTemplatedEmail
 * @package Charcoal\Mailer\Templating
 */
class RawTemplatedEmail
{
    public readonly string $html;
    private ?string $plainText = null;

    use DataBindingTrait;

    /**
     * @param \Charcoal\Mailer\TemplatingEngine $engine
     * @param \Charcoal\Mailer\Templating\EmailTemplateFile $template
     * @param \Charcoal\Mailer\Templating\EmailBodyHtml $body
     * @param string $subject
     * @param string|null $preHeader
     * @throws \Charcoal\Mailer\Exceptions\DataBindException
     */
    public function __construct(
        public readonly TemplatingEngine $engine,
        EmailTemplateFile                $template,
        EmailBodyHtml                    $body,
        public readonly string           $subject,
        public readonly ?string          $preHeader = null
    )
    {
        $this->html = preg_replace('/\{\{body}}/', $body->html, $template->html);
        $this->bound = array_merge($this->engine->getBoundData(), $template->getBoundData());
        $this->set("subject", $this->subject);
        $this->set("preHeader", $this->preHeader ?? $this->subject);
    }

    /**
     * @param string $plainText
     * @return void
     */
    public function setPlainText(string $plainText): void
    {
        $this->plainText = $plainText;
    }

    /**
     * @param string $modifierStr
     * @param string $modifier
     * @return array
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
     */
    private function modifierArguments(string $modifierStr, string $modifier): array
    {
        $args = [];

        $argBufferStr = null;
        $argBufferBlob = "";
        for ($i = 0; $i < strlen($modifierStr); $i++) {
            $char = $modifierStr[$i];
            if ($char === '"') {
                if (is_string($argBufferStr)) { // Ending string buffer
                    $args[] = $argBufferStr;
                    $argBufferStr = null;
                    $argBufferBlob = "";
                } else { // Start string buffer
                    $argBufferStr = "";
                    $argBufferBlob = null;
                }

                continue;
            }

            if ($char === ':' && !$argBufferStr) {
                if (is_string($argBufferBlob) && $argBufferBlob) {
                    $args[] = $this->checkBlobArg($argBufferBlob, count($args) + 1, $modifier);
                }

                $argBufferBlob = "";
                continue;
            }

            if (is_string($argBufferStr)) {
                $argBufferStr .= $char;
                continue;
            }

            if (is_string($argBufferBlob)) {
                $argBufferBlob .= $char;
            }
        }

        return $args;
    }

    /**
     * @param string $blob
     * @param int $argNum
     * @param string $modifier
     * @return int|bool
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
     */
    private function checkBlobArg(string $blob, int $argNum, string $modifier): int|bool
    {
        if (in_array($blob, ["true", "false"])) {
            return boolval($blob);
        }

        if (preg_match('/^[0-9]+$/', $blob)) {
            return intval($blob);
        }

        throw new TemplatingException(sprintf('Invalid argument %d for modifier "%s"', $argNum, $modifier));
    }

    /**
     * @return string
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
     */
    public function generateHTML(): string
    {
        return preg_replace_callback('/\{\{\w+(\.\w+)*(\|\w+(:((\"[\w\s:\-.]+\")|([0-9]+)|true|false))*)*}}/', function (array $match) {
            $match = explode("|", substr($match[0], 2, -2));
            $value = $this->get(array_shift($match));
            foreach ($match as $modifierStr) {
                $modifierStr = explode(":", $modifierStr);
                $modifier = array_shift($modifierStr);
                $modifierStr = implode(":", $modifierStr);
                $modifierArguments = $this->modifierArguments($modifierStr, $modifier);
                unset($modifierStr);

                $value = $this->engine->modifiers->apply($modifier, $value, $modifierArguments);
            }

            if (is_array($value)) {
                $value = "Array";
            }

            if (!is_string($value)) {
                $value = strval($value);
            }

            return $value;
        }, $this->html);
    }

    /**
     * @param \Charcoal\Mailer\Message\Sender|null $sender
     * @return \Charcoal\Mailer\Message
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
     */
    public function compose(?Sender $sender = null): Message
    {
        $body = new Body($this->generateHTML(), $this->plainText);
        return $this->engine->mailer->compose($this->subject, $body, $sender);
    }
}