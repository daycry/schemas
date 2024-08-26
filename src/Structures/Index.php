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

class Index extends Mergeable
{
    /**
     * The index name.
     *
     * @var string
     */
    public $name;

    public function __construct($indexData = null)
    {
        if (empty($indexData)) {
            return;
        }

        if (is_string($indexData)) {
            $this->name = $indexData;
        } else {
            foreach ($indexData as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}
