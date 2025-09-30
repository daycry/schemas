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

namespace Tests\Exceptions;

use Daycry\Schemas\Exceptions\SchemasException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SchemasExceptionTest extends TestCase
{
    public function testFactoryMethodsReturnInstances(): void
    {
        $e1  = SchemasException::forMissingField('User', 'email');
        $e2  = SchemasException::forUnsupportedHandler('FooHandler');
        $e3  = SchemasException::forMethodNotImplemented('Bar', 'do');
        $e4  = SchemasException::forNoSchema();
        $e5  = SchemasException::forReaderNotReady();
        $e6  = SchemasException::forInvalidPluginConfiguration('PluginX');
        $e7  = SchemasException::forMissingPlugin('PluginY');
        $e8  = SchemasException::forPluginDependencyNotMet('PluginA', 'PluginB');
        $e9  = SchemasException::forIncompatiblePlugin('PluginZ', '1.0');
        $e10 = SchemasException::forPluginAlreadyRegistered('PluginX');
        $e11 = SchemasException::forMissingSchema();
        $e12 = SchemasException::forMissingArchiveHandler('cache');
        $e13 = SchemasException::forMissingReadHandler('json');

        $this->assertInstanceOf(SchemasException::class, $e1);
        $this->assertInstanceOf(SchemasException::class, $e13);
        $this->assertNotSame($e1, $e2); // ensure new instances
        $this->assertStringContainsString('Plugin', $e6->getMessage());
        $this->assertStringContainsString('not found', $e7->getMessage());
        $this->assertStringContainsString('requires dependency', $e8->getMessage());
    }
}
