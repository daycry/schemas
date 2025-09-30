<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Daycry\Schemas\Structures;

final class Field extends Mergeable
{
    public string $name         = '';
    public bool $primary_key    = false;
    public ?string $type        = null;
    public ?int $max_length     = null;
    public bool $nullable       = false;
    public mixed $default       = null;
    public bool $auto_increment = false;
    public ?string $comment     = null;

    /**
     * @param array<string,mixed>|string|null $fieldData
     */
    public function __construct(array|string|null $fieldData = null)
    {
        if ($fieldData === null || $fieldData === '') {
            return;
        }

        if (is_string($fieldData)) {
            $this->name = $fieldData;

            return;
        }

        foreach ($fieldData as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }

            // Normalize booleans that might come as int/string from drivers
            if (in_array($key, ['primary_key', 'nullable', 'auto_increment'], true)) {
                $value = (bool) $value; // cast 0/1/'0'/'1' etc.
            }

            /** @phpstan-ignore-next-line dynamic assignment guarded */
            $this->{$key} = $value;
        }
    }
}
