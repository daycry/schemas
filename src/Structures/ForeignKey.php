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

final class ForeignKey extends Mergeable
{
    public string $constraint_name      = '';
    public ?string $column_name         = null;
    public ?string $foreign_table_name  = null;
    public ?string $foreign_column_name = null;
    public ?string $on_delete           = null;
    public ?string $on_update           = null;

    /**
     * @param array<string,mixed>|string|null $foreignKeyData
     */
    public function __construct(array|string|null $foreignKeyData = null)
    {
        if ($foreignKeyData === null || $foreignKeyData === '') {
            return;
        }
        if (is_string($foreignKeyData)) {
            $this->constraint_name = $foreignKeyData;

            return;
        }

        foreach ($foreignKeyData as $key => $value) {
            if (! property_exists($this, $key)) {
                continue;
            }

            // Normalize column names that might come as arrays from drivers
            if (in_array($key, ['column_name', 'foreign_column_name'], true) && is_array($value)) {
                $value = $value[0] ?? null; // take first element or null
            }

            /** @phpstan-ignore-next-line */
            $this->{$key} = $value;
        }
    }
}
