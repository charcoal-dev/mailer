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

namespace Charcoal\Mailer\Templating;

use Charcoal\Mailer\Exception\DataBindException;

/**
 * Trait DataBindingTrait
 * @package Charcoal\Mailer\Templating
 */
trait DataBindingTrait
{
    /** @var array */
    protected array $bound = [];

    /**
     * @param string $key
     * @param string|int|float|array|object $value
     * @return $this
     * @throws \Charcoal\Mailer\Exception\DataBindException
     */
    public function set(string $key, string|int|float|array|object $value): static
    {
        $this->bound[strtolower($key)] = is_array($value) || is_object($value) ?
            $this->sanitizeArray($value, [$key]) : $this->sanitizeValue($value, [$key]);
        return $this;
    }

    /**
     * @param string|array $key
     * @return string|int|array|null
     */
    public function get(string|array $key): string|int|array|null
    {
        if (is_string($key)) {
            $key = [$key];
        }

        $key = implode(".", $key);
        $key = strtolower(trim(trim($key), "."));
        $key = strpos($key, ".") ? explode(".", $key) : [$key];

        $valuePointer = implode("", array_map(function ($prop) {
            return "[\"" . $prop . "\"]";
        }, $key));

        $value = null;
        eval("\$value = \$this->bound" . $valuePointer . " ?? null;");
        /** @var string|int|array $value */
        return $value;
    }

    /**
     * @return array
     */
    public function getBoundData(): array
    {
        return $this->bound;
    }

    /**
     * @param array|object $data
     * @param array $key
     * @return array
     * @throws \Charcoal\Mailer\Exception\DataBindException
     */
    private function sanitizeArray(array|object $data, array $key): array
    {
        try {
            $data = json_decode(
                json_encode($data, JSON_THROW_ON_ERROR),
                true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw new DataBindException(sprintf('Failed to apply JSON filter to data "%s"', implode(".", $key)));
        }

        $sanitized = [];
        foreach ($data as $keyOrIndex => $value) {
            $propKeys = array_merge($key, [strval($keyOrIndex)]);
            $propValue = is_array($value) || is_object($value) ?
                $this->sanitizeArray($value, $propKeys) : $this->sanitizeValue($value, $propKeys);

            if (is_string($keyOrIndex)) {
                $sanitized[strtolower($keyOrIndex)] = $propValue;
            } else {
                $sanitized[] = $propValue;
            }
        }

        return $sanitized;
    }

    /**
     * @param string|int|float|bool|null $value
     * @param array $key
     * @return string|int|null
     * @throws \Charcoal\Mailer\Exception\DataBindException
     */
    private function sanitizeValue(string|int|float|bool|null $value, array $key): string|int|null
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        if (is_float($value)) {
            $value = strval($value);
        }

        if (is_null($value)) {
            return null;
        }

        if (!is_string($value) && !is_int($value)) {
            throw new DataBindException(
                sprintf('Invalid value of type "%s" for key "%s"', gettype($value), implode(".", $key))
            );
        }

        return $value;
    }
}