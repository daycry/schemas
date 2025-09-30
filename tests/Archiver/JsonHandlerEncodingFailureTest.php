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

namespace Tests\Archiver;

use Daycry\Schemas\Archiver\Handlers\JsonHandler;
use Daycry\Schemas\Structures\Schema;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class JsonHandlerEncodingFailureTest extends TestCase
{
    public function testArchiveEncodingFailure(): void
    {
        // Stub handler that returns an unserializable value to trigger JSON_THROW_ON_ERROR
        $handler = new class (null, sys_get_temp_dir() . '/schema_fail_' . uniqid() . '.json') extends JsonHandler {
            protected function convertSchemaToArray(Schema $schema): array
            {
                // JSON cannot encode a resource with JSON_THROW_ON_ERROR
                $res = fopen('php://memory', 'rb');

                return ['schema' => ['bad' => $res]]; // provoke failure
            }
        };

        $result = $handler->archive(new Schema());
        $this->assertFalse($result);
        $errors = $handler->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('JSON encoding failed', $errors[0]);
    }
}
