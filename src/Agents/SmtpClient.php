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

use Charcoal\Mailer\Enums\EOL;
use Charcoal\Mailer\Exceptions\SmtpClientException;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class SmtpClient
 * @package Charcoal\Mailer\Agents
 */
class SmtpClient implements MailerAgentInterface
{
    public bool $keepAlive = false;
    public EOL $eolChar;
    private array $streamContextOptions = [];
    private array $serverOptions;
    private string $lastResponse = "";
    private int $lastResponseCode = 0;

    /** @var null|resource */
    private $stream;

    /**
     * @param string $host
     * @param int $port
     * @param string $domain
     * @param bool $useTlsEncryption
     * @param string|null $username
     * @param string|null $password
     * @param int $timeOut
     */
    public function __construct(
        public readonly string      $host,
        public readonly int         $port,
        public readonly string      $domain,
        public readonly bool        $useTlsEncryption = true,
        public readonly string|null $username = null,
        public readonly string|null $password = null,
        public int                  $timeOut = 1
    )
    {
        $this->stream = null;
        $this->eolChar = \Charcoal\Mailer\Enums\EOL::from(PHP_EOL);
        $this->resetServerOptions();
    }

    /**
     * Set stream context options
     * @param array $options
     * @return SmtpClient
     * @api
     */
    public function streamContextOptions(array $options): static
    {
        $this->streamContextOptions = $options;
        return $this;
    }

    /**
     * Establish connection to SMTP server or revive existing one
     * @return void
     * @throws \Charcoal\Mailer\Exceptions\SmtpClientException
     */
    public function connect(): void
    {
        if (!$this->stream) {
            $errorNum = 0;
            $errorMsg = "";
            $context = stream_context_create($this->streamContextOptions);
            $this->stream = stream_socket_client(
                sprintf('%1$s:%2$d', $this->host, $this->port),
                $errorNum,
                $errorMsg,
                $this->timeOut,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$this->stream) {
                throw SmtpClientException::ConnectionError($errorNum, $errorMsg);
            }

            $this->read(); // Read response from server
            if ($this->lastResponseCode() !== 220) {
                throw SmtpClientException::UnexpectedResponse("CONNECT", 220, $this->lastResponseCode());
            }

            // Fetch options/specs available to client domain from SMTP server
            $this->smtpServerOptions(
                $this->command("EHLO", $this->domain)
            );

            // Use TLS?
            if ($this->useTlsEncryption === true) {
                if ($this->serverOptions["startTLS"] !== true) {
                    throw SmtpClientException::TlsNotAvailable();
                }

                $this->command("STARTTLS", null, 220);
                $tls = stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$tls) {
                    throw SmtpClientException::TlsNegotiateFailed();
                }

                $this->command("EHLO", $this->domain); // Resend EHLO command
            }

            // Authenticate
            if ($this->serverOptions["authLogin"] === true) {
                try {
                    $this->command("AUTH LOGIN", null, 334);
                    $this->command(base64_encode($this->username ?? " "), null, 334);
                    $this->command(base64_encode($this->password ?? " "), null, 235);
                } catch (SmtpClientException) {
                    throw SmtpClientException::AuthFailed($this->lastResponse);
                }
            } elseif ($this->serverOptions["authPlain"] === true) {
                throw SmtpClientException::AuthUnavailable();
            } else {
                throw SmtpClientException::AuthUnavailable();
            }
        } else {
            try {
                if (!stream_get_meta_data($this->stream)["timed_out"]) {
                    throw SmtpClientException::TimedOut();
                }

                $this->command("NOOP", null, 250);
            } catch (SmtpClientException) {
                $this->stream = null;
                $this->connect();
                return;
            }
        }
    }

    /**
     * @return array
     * @api
     */
    public function getServerOptions(): array
    {
        return $this->serverOptions;
    }

    /**
     * @return void
     * @api
     */
    public function disconnect(): void
    {
        $this->stream = null;
        $this->resetServerOptions();
    }

    /**
     * @return void
     */
    private function resetServerOptions(): void
    {
        $this->serverOptions = [
            "startTLS" => false,
            "authLogin" => false,
            "authPlain" => false,
            "size" => 0,
            "8Bit" => false
        ];
    }

    /**
     * Parse response for server's options available to client domain
     * @param string $response
     */
    private function smtpServerOptions(string $response): void
    {
        $this->resetServerOptions();

        // Read from response
        $lines = explode($this->eolChar->value, $response);
        foreach ($lines as $line) {
            $code = intval(substr($line, 0, 3));
            $spec = substr($line, 4);
            if ($spec && in_array($code, [220, 250])) {
                $spec = explode(" ", strtolower($spec));
                if ($spec[0] === "auth") {
                    if (in_array("plain", $spec)) {
                        $this->serverOptions["authPlain"] = true;
                    }

                    if (in_array("login", $spec)) {
                        $this->serverOptions["authLogin"] = true;
                    }
                } elseif ($spec[0] === "size") {
                    $this->serverOptions["size"] = intval($spec[1]);
                } elseif ($spec[0] === "8bitmime") {
                    $this->serverOptions["8Bit"] = true;
                } elseif ($spec[0] === "starttls") {
                    $this->serverOptions["startTLS"] = true;
                }
            }
        }
    }

    /**
     * Send command to server, read response, and make sure response code matches expected code
     * @param string $command
     * @param string|null $args
     * @param int $expect
     * @return string
     * @throws \Charcoal\Mailer\Exceptions\SmtpClientException
     */
    public function command(string $command, string $args = null, int $expect = 0): string
    {
        $sendCommand = $args ? sprintf('%1$s %2$s', $command, $args) : $command;
        $this->write($sendCommand);
        $response = $this->read();
        $responseCode = $this->lastResponseCode();

        if ($expect > 0) {
            if ($responseCode !== $expect) {
                throw SmtpClientException::UnexpectedResponse($command, $expect, $responseCode);
            }
        }

        return $response;
    }

    /**
     * Send command/data to SMTP server
     * @param string $command
     */
    private function write(string $command): void
    {
        fwrite($this->stream, $command . $this->eolChar->value);
    }

    /**
     * Read response from SMTP server
     * @return string
     */
    private function read(): string
    {
        $this->lastResponse = fread($this->stream, 1024); // Read up to 1KB
        $this->lastResponseCode = intval(explode(" ", $this->lastResponse)[0]);
        $this->lastResponseCode = $this->lastResponseCode > 0 ? $this->lastResponseCode : -1;
        return $this->lastResponse;
    }

    /**
     * @return string
     * @api
     */
    public function lastResponse(): string
    {
        return $this->lastResponse;
    }

    /**
     * @return int
     */
    public function lastResponseCode(): int
    {
        return $this->lastResponseCode;
    }

    /**
     * @param \Charcoal\Mailer\Message|\Charcoal\Mailer\Message\CompiledMimeMessage $message
     * @param array $recipients
     * @return int
     * @throws \Charcoal\Mailer\Exceptions\EmailComposeException
     * @throws \Charcoal\Mailer\Exceptions\SmtpClientException
     */
    public function send(Message|CompiledMimeMessage $message, array $recipients): int
    {
        if ($message instanceof Message) {
            $message = $message->compile();
        }

        $this->connect(); // Establish or revive connection
        $this->command("RSET"); // Reset SMTP buffer

        $this->command(sprintf('MAIL FROM:<%1$s>', $message->senderEmail), null, 250); // Set mail from
        $count = 0;
        foreach ($recipients as $email) {
            $this->write(sprintf('RCPT TO:<%1$s>', $email));
            $this->read();
            if ($this->lastResponseCode !== 250) {
                throw SmtpClientException::InvalidRecipient(substr($this->lastResponse, 4));
            }

            $count++;
        }

        $messageMimeSize = strlen($message->compiled);
        if ($this->serverOptions["size"] > 0 && $messageMimeSize > $this->serverOptions["size"]) {
            throw SmtpClientException::ExceedsMaximumSize($messageMimeSize, $this->serverOptions["size"]);
        }

        $this->command("DATA", null, 354);
        $this->write($message->compiled); // Write MIME
        $this->command(".", null, 250); // End DATA

        // Keep alive?
        if (!$this->keepAlive) {
            $this->write("QUIT"); // Send QUIT command
            //unset($this->stream);
            $this->stream = null;  // Close stream resource
        }

        return $count;
    }
}