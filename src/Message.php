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

use Charcoal\Mailer\Exception\EmailComposeException;
use Charcoal\Mailer\Message\Attachment;
use Charcoal\Mailer\Message\Body;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Message\EndOfLine;
use Charcoal\Mailer\Message\Sender;

/**
 * Class Message
 * @package Charcoal\Mailer
 */
class Message
{
    public Sender $sender;
    public EndOfLine $eolChar;
    private array $headers = [];
    private array $attachments = [];

    /**
     * @param \Charcoal\Mailer\Mailer $mailer
     * @param string $subject
     * @param \Charcoal\Mailer\Message\Body $body
     * @param \Charcoal\Mailer\Message\Sender|null $sender
     */
    public function __construct(
        public readonly Mailer $mailer,
        public readonly string $subject,
        public readonly Body   $body,
        ?Sender                $sender = null,
    )
    {
        $this->sender = $sender ?? $mailer->sender;
        $this->eolChar = $mailer->clientConfig->eolChar;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
     */
    public function header(string $key, string $value): static
    {
        if (in_array(strtolower($key), ["from", "subject", "content-type", "x-mailer"])) {
            throw EmailComposeException::DisabledHeaderKey($key);
        }

        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param string $filePath
     * @param string|null $name
     * @param string|null $contentType
     * @param string $disposition
     * @param string|null $contentId
     * @return \Charcoal\Mailer\Message\Attachment
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
     */
    public function attach(
        string  $filePath,
        ?string $name = null,
        ?string $contentType = null,
        string  $disposition = "attachment",
        ?string $contentId = null
    ): Attachment
    {
        $attachment = new Attachment($filePath, $name, $contentType, $disposition, $contentId);
        $this->attachments[] = $attachment;
        return $attachment;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param string $separator
     * @return \Charcoal\Mailer\Message\CompiledMimeMessage
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
     */
    public function compile(string $separator = ""): CompiledMimeMessage
    {
        // MIME Boundaries
        $boundaries = $this->mailer->clientConfig->getMimeBoundaries(
            md5(uniqid(sprintf("%s-%s", $this->subject, microtime(false))))
        );

        // Headers
        $headers[] = $this->sender->name ?
            sprintf('From: %1$s <%2$s>', $this->sender->name, $this->sender->email) :
            sprintf('From:<%1$s>', $this->sender->email);
        $headers[] = sprintf('Subject: %1$s', $this->subject);
        $headers[] = "MIME-Version: 1.0";
        $headers[] = sprintf('X-Mailer: %s', $this->mailer->clientConfig->name);
        $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%1$s"', substr($boundaries[0], 2));
        foreach ($this->headers as $key => $value) {
            $headers[] = sprintf('%1$s: %2$s', $key, $value);
        }

        $headers[] = $separator; // Separator line between headers and body

        // Body
        $body[] = "This is a multi-part message in MIME format.";
        $body[] = $boundaries[0];
        $body[] = sprintf('Content-Type: multipart/alternative; boundary="%1$s"', substr($boundaries[1], 2));
        $body[] = ""; // Empty line

        // Body: text/plain
        if ($this->body->plainText) {
            $encoding = $this->checkBodyEncoding($this->body->plainText);
            $body[] = $boundaries[1];
            $body[] = sprintf('Content-Type: text/plain; charset=%1$s', $encoding[0]);
            $body[] = sprintf('Content-Transfer-Encoding: %1$s', $encoding[1]);
            $body[] = ""; // Empty line
            $body[] = $this->body->plainText;
        }

        // Body: text/html
        if ($this->body->html) {
            $encoding = $this->checkBodyEncoding($this->body->html);
            $body[] = $boundaries[1];
            $body[] = sprintf('Content-Type: text/html; charset=%1$s', $encoding[0]);
            $body[] = sprintf('Content-Transfer-Encoding: %1$s', $encoding[1]);
            $body[] = ""; // Empty line
            $body[] = $this->body->html;
        }

        // Attachments
        foreach ($this->attachments as $attachment) {
            /** @var $attachment Attachment */
            $body[] = $boundaries[0];
            $body[] = implode($this->eolChar->value, $attachment->mime());
        }

        // Compile
        $mime = array_merge($headers, $body);
        return new CompiledMimeMessage(
            $this->subject,
            implode($this->eolChar->value, $mime),
            $this->sender
        );
    }

    /**
     * @param string $body
     * @return array
     */
    private function checkBodyEncoding(string $body): array
    {
        return preg_match("/[\x80-\xFF]/", $body) ? ["utf-8", "8Bit"] : ["us-ascii", "7Bit"];
    }
}