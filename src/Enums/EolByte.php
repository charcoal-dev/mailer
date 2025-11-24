<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer\Enums;

/**
 * Enumeration representing different types of End-Of-Line (EOL) characters.
 */
enum EolByte: string
{
    case Unix = "\n";
    case Windows = "\r\n";
    case CR = "\r";
}