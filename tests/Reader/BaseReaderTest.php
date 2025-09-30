<?php

declare(strict_types=1);

namespace Tests\Reader;

use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Exceptions\SchemasException;
use Daycry\Schemas\Reader\BaseReader;
use PHPUnit\Framework\TestCase;

class BaseReaderConcrete extends BaseReader
{
    // expose ensureReady for testing via a wrapper
    public function callEnsureReady(): bool
    {
        // use reflection to call protected ensureReady not necessary: subclass can call
        return $this->ensureReady();
    }
}

final class BaseReaderTest extends TestCase
{
    public function testReadyFalseByDefault(): void
    {
        $reader = new BaseReaderConcrete(new Schemas());
        $this->assertFalse($reader->ready());
    }

    public function testEnsureReadySilentFalseThrows(): void
    {
        $config = new Schemas();
        $config->silent = false;
        $reader = new BaseReaderConcrete($config);
        $this->expectException(SchemasException::class);
        $reader->callEnsureReady();
    }

    public function testEnsureReadySilentTrueCollectsError(): void
    {
        $config = new Schemas();
        $config->silent = true;
        $reader = new BaseReaderConcrete($config);
        $this->assertFalse($reader->callEnsureReady());
        $errors = $reader->getErrors();
        $this->assertNotEmpty($errors);
    }
}
