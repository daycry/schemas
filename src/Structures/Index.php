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

final class Index extends Mergeable
{
    public string $name = '';

    /**
     * @var list<string>
     */
    public array $fields = [];

    public ?string $type = null;
    public bool $unique  = false;

    /**
     * @param array<string,mixed>|string|null $indexData
     */
    public function __construct(array|string|null $indexData = null)
    {
        if ($indexData === null || $indexData === '') {
            return;
        }
        if (is_string($indexData)) {
            $this->name = $indexData;

            return;
        }

        foreach ($indexData as $key => $value) {
            if (property_exists($this, $key)) {
                /** @phpstan-ignore-next-line dynamic assign */
                $this->{$key} = $value;
            }
        }
    }
}
