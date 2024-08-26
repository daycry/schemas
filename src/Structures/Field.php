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

class Field extends Mergeable
{
    /**
     * The field name.
     *
     * @var string
     */
    public $name;

    /**
     * Whether this is a primary key.
     *
     * @var bool
     */
    public $primary_key;

    public function __construct($fieldData = null)
    {
        if (empty($fieldData)) {
            return;
        }

        if (is_string($fieldData)) {
            $this->name = $fieldData;
        } else {
            foreach ($fieldData as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}
