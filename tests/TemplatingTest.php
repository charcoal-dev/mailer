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
 * Class TemplatingTest
 */
class TemplatingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return void
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function testBodyLoad(): void
    {
        $signupTpl = $this->getTemplatingEngine()->getBody("signup");
        $this->assertTrue(str_starts_with($signupTpl->html, "<p>Dear {{user.name}},"), "Beginning of HTML body");
        $this->assertTrue(str_ends_with($signupTpl->html, "Thank you!</p>"), "Ending of HTML body");
    }

    /**
     * @return void
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function testNonExistentBody(): void
    {
        $this->expectException(\Charcoal\Mailer\Exception\TemplatingException::class);
        $this->getTemplatingEngine()->getBody("this_doesnt_exist");
    }

    /**
     * @return void
     * @throws \Charcoal\Mailer\Exception\DataBindException
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function testTemplateEmail(): void
    {
        $templatingEngine = $this->getTemplatingEngine();
        $templatingEngine->modifiers->registerDefaultModifiers();

        // Data binding on TemplateEngine level
        $templatingEngine->set("config", [
            "title" => "Charcoal PHP Framework",
            "domain" => "charcoal.dev"
        ]);

        $defaultTemplate = new \Charcoal\Mailer\Templating\EmailTemplateFile("default", "template.html");
        $templatingEngine->registerTemplate($defaultTemplate);
        $this->assertTrue(str_starts_with($defaultTemplate->html, "<!DOCTYPE html>"), "Beginning of HTML template");
        $this->assertTrue(str_ends_with($defaultTemplate->html, "</html>"), "Ending of HTML template");

        // Data binging on HTML template level
        $defaultTemplate->set("template", "default");

        // TemplateEngine level data does NOT YET exist on template
        $this->assertNull($defaultTemplate->get("config"));

        // Build a templated email body
        $signupBody = $templatingEngine->getBody("signup");

        // Compose an email
        $signupEmail = $templatingEngine->create($defaultTemplate, $signupBody, "Account registration");

        // On e-mail message, both TemplateEngine and EmailTemplateFile bound data IS AVAILABLE
        $this->assertIsArray($signupEmail->get("config"));
        $this->assertEquals("charcoal.dev", $signupEmail->get("config")["domain"]);
        $this->assertEquals("default", $signupEmail->get("template"));

        // Data binding on e-mail message/body level
        $signupEmail->set("user", ["name" => "Furqan"]);
        $signupEmail->set("now", time());

        // Generate HTML
        $signupEmailHtml = preg_split("/\n|\r\n/", $signupEmail->generateHTML());

        // Crosscheck data binding
        // <title>{{subject}} | {{config.title}}</title>
        $this->assertEquals("<title>Account registration | Charcoal PHP Framework</title>", trim($signupEmailHtml[5]));
        // <h1>{{subject}}</h1>
        $this->assertEquals("<h1>Account registration</h1>", trim($signupEmailHtml[43]));
        // <p>Dear {{user.name}},</p>
        $this->assertEquals("<p>Dear Furqan,</p>", trim($signupEmailHtml[46]));
        // <p>&copy; {{now|date:"Y"}} {{config.title}}. All rights reserved.</p>
        $this->assertEquals("<p>&copy; " . date("Y") . " Charcoal PHP Framework. All rights reserved.</p>", trim($signupEmailHtml[51]));
    }

    /**
     * @return \Charcoal\Mailer\TemplatingEngine
     */
    private function getTemplatingEngine(): \Charcoal\Mailer\TemplatingEngine
    {
        return new \Charcoal\Mailer\TemplatingEngine(new Charcoal\Mailer\Mailer(
            new \Charcoal\Mailer\Message\Sender("robot@charcoal.dev", "Charcoal-Dev")
        ), __DIR__ . DIRECTORY_SEPARATOR . "bodies");
    }
}
