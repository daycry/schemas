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

class ForeignKey extends Mergeable
{
    /**
     * The foreign key constraint name.
     *
     * @var string
     */
    public $constraint_name;

    public function __construct($foreignKeyData = null)
    {
        if (empty($foreignKeyData)) {
            return;
        }

        if (is_string($foreignKeyData)) {
            $this->constraint_name = $foreignKeyData;
        } else {
            foreach ($foreignKeyData as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}
