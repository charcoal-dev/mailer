<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Smtp;

use Charcoal\Mailer\Contracts\MailProviderInterface;
use Charcoal\Mailer\Contracts\SmtpConfigInterface;
use Charcoal\Mailer\Exceptions\SmtpClientException;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * The SmtpClient class is responsible for establishing and managing connections
 * to an SMTP server, along with sending messages using the SMTP protocol.
 * It implements the MailProviderInterface to provide email sending functionality.
 */
final class SmtpClient implements MailProviderInterface
{
    public bool $keepAlive = false;
    private array $streamContextOptions = [];
    private array $serverOptions;
    private string $lastResponse = "";
    private int $lastResponseCode = 0;

    /** @var null|resource */
    private $stream;

    /**
     * @param SmtpConfigInterface $config
     */
    public function __construct(public readonly SmtpConfigInterface $config)
    {
        $this->stream = null;
        $this->resetServerOptions();
    }

    /**
     * Set stream context options
     * @param array $options
     * @return SmtpClient
     * @api
     */
    public function streamContextOptions(array $options): self
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
                $this->config->getHostString(),
                $errorNum,
                $errorMsg,
                $this->config->getTimeout(),
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$this->stream) {
                throw SmtpClientException::ConnectionError($errorNum, $errorMsg);
            }

            $this->read(); // Read response from the server
            if ($this->lastResponseCode() !== 220) {
                throw SmtpClientException::UnexpectedResponse("CONNECT", 220, $this->lastResponseCode());
            }

            // Fetch options/specs available to client domain from SMTP server
            $this->smtpServerOptions(
                $this->command("EHLO", $this->config->getDomain())
            );

            // Use TLS?
            if ($this->config->useTls() === true) {
                if ($this->serverOptions["startTLS"] !== true) {
                    throw SmtpClientException::TlsNotAvailable();
                }

                $this->command("STARTTLS", null, 220);
                $tls = stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$tls) {
                    throw SmtpClientException::TlsNegotiateFailed();
                }

                $this->command("EHLO", $this->config->getDomain()); // Resend EHLO command
            }

            // Authenticate
            if ($this->serverOptions["authLogin"] === true) {
                try {
                    $this->command("AUTH LOGIN", null, 334);
                    $this->command(base64_encode($this->config->getUsername() ?? " "), null, 334);
                    $this->command(base64_encode($this->config->getPassword() ?? " "), null, 235);
                } catch (SmtpClientException) {
                    throw SmtpClientException::AuthFailed($this->lastResponse);
                }
            } elseif ($this->serverOptions["authPlain"] === true) {
                try {
                    $auth = base64_encode("\0" . $this->config->getUsername() . "\0" . $this->config->getPassword());
                    $this->command("AUTH PLAIN", $auth, 235);
                } catch (SmtpClientException) {
                    throw SmtpClientException::AuthFailed($this->lastResponse);
                }
            } else {
                throw SmtpClientException::AuthUnavailable();
            }
        } else {
            try {
                $meta = stream_get_meta_data($this->stream);
                if ($meta["timed_out"]) {
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
        if ($this->stream) {
            fclose($this->stream);
        }
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
        $lines = explode("\r\n", $response);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $code = intval(substr($line, 0, 3));
            $spec = substr($line, 4);
            if ($spec && in_array($code, [220, 250])) {
                $specArgs = explode(" ", strtolower($spec));
                $verb = $specArgs[0];
                if ($verb === "auth") {
                    if (in_array("plain", $specArgs)) {
                        $this->serverOptions["authPlain"] = true;
                    }

                    if (in_array("login", $specArgs)) {
                        $this->serverOptions["authLogin"] = true;
                    }
                } elseif ($verb === "size") {
                    $this->serverOptions["size"] = isset($specArgs[1]) ? intval($specArgs[1]) : 0;
                } elseif ($verb === "8bitmime") {
                    $this->serverOptions["8Bit"] = true;
                } elseif ($verb === "starttls") {
                    $this->serverOptions["startTLS"] = true;
                }
            }
        }
    }

    /**
     * Send command to server, read the response, and make sure response code matches expected code
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
        fwrite($this->stream, $command . "\r\n");
    }

    /**
     * Read response from SMTP server
     * @return string
     */
    private function read(): string
    {
        $this->lastResponse = "";
        while ($line = fgets($this->stream, 1024)) {
            $this->lastResponse .= $line;
            // Check if this is the last line of a multi-line response (RFC 5321)
            // A space after the response code indicates the last line.
            if (isset($line[3]) && $line[3] === " ") {
                break;
            }

            // If it's a single-line response without space (rare but possible in some implementations)
            if (strlen($line) >= 3 && !isset($line[3])) {
                break;
            }
        }

        $this->lastResponseCode = intval(substr($this->lastResponse, 0, 3));
        if ($this->lastResponseCode <= 0) {
            $this->lastResponseCode = -1;
        }
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
     * @param Message|CompiledMimeMessage $message
     * @param array $recipients
     * @return int
     * @throws SmtpClientException
     * @throws \Charcoal\Mailer\Exceptions\EmailComposeException
     */
    public function send(Message|CompiledMimeMessage $message, array $recipients): int
    {
        if ($message instanceof Message) {
            $message = $message->compile();
        }

        $this->connect(); // Establish or revive connection
        $this->command("RSET"); // Reset SMTP buffer

        $this->command(sprintf('MAIL FROM:<%1$s>', $message->sender->email), null, 250); // Set mail from
        $count = 0;
        foreach ($recipients as $email) {
            $this->command(sprintf('RCPT TO:<%1$s>', $email), null, 250);
            $count++;
        }

        $messageMimeSize = strlen($message->compiledMimeBody);
        if ($this->serverOptions["size"] > 0 && $messageMimeSize > $this->serverOptions["size"]) {
            throw SmtpClientException::ExceedsMaximumSize($messageMimeSize, $this->serverOptions["size"]);
        }

        $this->command("DATA", null, 354);

        // Period stuffing (RFC 5321 Section 4.5.2)
        $data = $message->compiledMimeBody;
        if (str_starts_with($data, ".")) {
            $data = "." . $data;
        }

        $data = str_replace("\r\n.", "\r\n..", $data);
        $this->write($data); // Write MIME
        $this->command(".", null, 250); // End DATA

        // Keep alive?
        if (!$this->keepAlive) {
            try {
                $this->command("QUIT", null, 221);
            } catch (SmtpClientException) {
            }

            $this->disconnect();
        }

        return $count;
    }
}