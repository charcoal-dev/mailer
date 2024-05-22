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

/**
 * Class SmtpClientTest
 */
class SmtpClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Mailer\Exception\SmtpClientException
     */
    public function testSmtpConnection(): void
    {
        $smtpConfig = include "SmtpConfig.php";
        $smtpClient = new \Charcoal\Mailer\Agents\SmtpClient(
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