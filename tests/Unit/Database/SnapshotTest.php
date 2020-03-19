<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database;

use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Database\Snapshot;
use Kreait\Firebase\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
class SnapshotTest extends UnitTestCase
{
    /** @var Reference|MockObject */
    private $reference;

    /** @var Snapshot */
    private $snapshotWithArrayValue;

    /** @var Snapshot */
    private $snapshotWithScalarValue;

    /** @var Snapshot */
    private $snapshotWithEmptyValue;

    protected function setUp(): void
    {
        $this->reference = $this->createMock(Reference::class);

        $this->snapshotWithArrayValue = new Snapshot($this->reference, ['key' => ['subkey' => 'value']]);
        $this->snapshotWithScalarValue = new Snapshot($this->reference, 'string');
        $this->snapshotWithEmptyValue = new Snapshot($this->reference, null);
    }

    public function testGetReference(): void
    {
        $this->assertSame($this->reference, $this->snapshotWithArrayValue->getReference());
    }

    public function testGetKey(): void
    {
        $this->reference->method('getKey')->willReturn('key');

        $this->assertSame('key', $this->snapshotWithArrayValue->getKey());
    }

    public function testGetChildOnANonArrayValueReturnsAnEmptySnapshot(): void
    {
        $this->reference->expects($this->once())
            ->method('getChild')->with('path/to/child')
            ->willReturn($this->createMock(Reference::class));

        $this->assertFalse($this->snapshotWithScalarValue->hasChild('path/to/child'));
        $childSnapshot = $this->snapshotWithScalarValue->getChild('path/to/child');

        $this->assertNull($childSnapshot->getValue());
    }

    public function testGetChildOnANonExistingChildReturnsAnEmptySnapshot(): void
    {
        $this->reference->expects($this->once())
            ->method('getChild')->with('nonexisting/child')
            ->willReturn($this->createMock(Reference::class));

        $this->assertFalse($this->snapshotWithArrayValue->hasChild('nonexisting/child'));
        $this->assertNull($this->snapshotWithArrayValue->getChild('nonexisting/child')->getValue());
    }

    public function testGetChild(): void
    {
        $this->reference->expects($this->once())
            ->method('getChild')->with('key/subkey')
            ->willReturn($this->createMock(Reference::class));

        $this->assertTrue($this->snapshotWithArrayValue->hasChild('key/subkey'));
        $this->assertSame('value', $this->snapshotWithArrayValue->getChild('key/subkey')->getValue());
    }

    public function testExists(): void
    {
        $this->assertTrue($this->snapshotWithArrayValue->exists());
        $this->assertTrue($this->snapshotWithScalarValue->exists());
        $this->assertFalse($this->snapshotWithEmptyValue->exists());
    }

    public function testHasChildren(): void
    {
        $this->assertTrue($this->snapshotWithArrayValue->hasChildren());
        $this->assertFalse($this->snapshotWithScalarValue->hasChildren());
        $this->assertFalse($this->snapshotWithEmptyValue->hasChildren());
    }

    public function testNumChildren(): void
    {
        $this->assertSame(1, $this->snapshotWithArrayValue->numChildren());
        $this->assertSame(0, $this->snapshotWithScalarValue->numChildren());
        $this->assertSame(0, $this->snapshotWithEmptyValue->numChildren());
    }

    /**
     * @see https://github.com/kreait/firebase-php/issues/212
     */
    public function testGetChildWithKeyStartingWithANonAlphabeticalCharacter(): void
    {
        $snapshot = new Snapshot($this->reference, [
            '123' => 'value',
            '-abc' => 'value',
        ]);

        $this->assertTrue($snapshot->hasChild('123'));
        $this->assertTrue($snapshot->hasChild('-abc'));
    }
}
