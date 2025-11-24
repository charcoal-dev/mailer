<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

/**
 * Class SmtpClientTest
 */
class SmtpClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Mailer\Exceptions\SmtpClientException
     */
    public function testSmtpConnection(): void
    {
        $smtpConfig = include "SmtpConfig.php";
        $smtpClient = new \Charcoal\Mailer\Smtp\SmtpClient(
            $smtpConfig["hostname"],
            $smtpConfig["port"],
            $smtpConfig["domain"],
            $smtpConfig["encryption"],
            $smtpConfig["username"],
            $smtpConfig["password"],
            $smtpConfig["timeout"],
        );

        $smtpClient->connect();
        $this->assertTrue(true);
    }
}