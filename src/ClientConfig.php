<?php
/**
 * Part of the "charcoal-dev/mailer" package.
 * @link https://github.com/charcoal-dev/mailer
 */

declare(strict_types=1);

namespace Charcoal\Mailer;

use Charcoal\Mailer\Enums\EolByte;

/**
 * Represents the configuration for a client, including name and MIME boundary options.
 */
final class ClientConfig
{
    public EolByte $eolChar;

    /**
     * @param string $name
     * @param string $boundary1Prefix
     * @param string $boundary2Prefix
     * @param string $boundary3Prefix
     */
    public function __construct(
        public string $name = "Charcoal Mailer " . Mailer::VERSION,
        public string $boundary1Prefix = "--Charcoal_B1",
        public string $boundary2Prefix = "--Charcoal_B2",
        public string $boundary3Prefix = "--Charcoal_B3",
    )
    {
        $this->eolChar = EolByte::from(PHP_EOL);
    }

    /**
     * @param string $uniqueId
     * @return array
     */
    public function getMimeBoundaries(string $uniqueId): array
    {
        $boundaries[] = $this->boundary1Prefix . $uniqueId;
        $boundaries[] = $this->boundary2Prefix . $uniqueId;
        $boundaries[] = $this->boundary3Prefix . $uniqueId;
        return $boundaries;
    }
}