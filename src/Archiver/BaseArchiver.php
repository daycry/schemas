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

namespace Daycry\Schemas\Archiver;

use CodeIgniter\Files\Exceptions\FileNotFoundException;
use CodeIgniter\Files\File;
use Daycry\Schemas\BaseHandler;

/**
 * Base Archiver Class
 *
 * Provides common methods for Archiver classes.
 */
abstract class BaseArchiver extends BaseHandler
{
    /**
     * Validate or create a file and write to it.
     *
     * @param string $path The path to the file
     *
     * @return bool Success or failure
     *
     * @throws FileNotFoundException
     */
    protected function putContents($path, string $data): bool
    {
        $file = new File($path);

        if (! $file->isWritable()) {
            if ($this->config->silent) {
                $this->errors[] = lang('Files.fileNotFound', [$path]);

                return false;
            }

            throw FileNotFoundException::forFileNotFound($path);
        }

        $file = $file->openFile('w');

        return (bool) $file->fwrite($data);
    }
}
