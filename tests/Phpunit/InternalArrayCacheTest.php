<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Tests\Phpunit;

use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;
use PHPUnit\Framework\TestCase;

final class InternalArrayCacheTest extends TestCase
{
    public function testSetEvictsOldestEntryWhenMaxSizeReached(): void
    {
        $cache = new InternalArrayCache(3);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $this->assertSame(3, $cache->getSize());
        $this->assertTrue($cache->has('key1'));

        $cache->set('key4', 'value4');

        $this->assertSame(3, $cache->getSize());
        $this->assertFalse($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));
        $this->assertTrue($cache->has('key3'));
        $this->assertTrue($cache->has('key4'));
    }

    public function testDeleteRemovesKey(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');
        $this->assertTrue($cache->has('key1'));

        $result = $cache->delete('key1');

        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
        $this->assertSame(0, $cache->getSize());
    }

    public function testDeleteNonExistentKeyReturnsTrue(): void
    {
        $cache = new InternalArrayCache();

        $result = $cache->delete('nonexistent');

        $this->assertTrue($result);
    }

    public function testGetMultipleReturnsValues(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $result = $cache->getMultiple(['key1', 'key2', 'key4']);

        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
            'key4' => null,
        ], $result);
    }

    public function testGetMultipleWithDefaultValue(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');

        $result = $cache->getMultiple(['key1', 'key2'], 'default');

        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'default',
        ], $result);
    }

    public function testSetMultipleSetsAllValues(): void
    {
        $cache = new InternalArrayCache();

        $result = $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $this->assertTrue($result);
        $this->assertSame('value1', $cache->get('key1'));
        $this->assertSame('value2', $cache->get('key2'));
        $this->assertSame('value3', $cache->get('key3'));
        $this->assertSame(3, $cache->getSize());
    }

    public function testSetMultipleRespectsMaxSize(): void
    {
        $cache = new InternalArrayCache(2);

        $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $this->assertSame(2, $cache->getSize());
        $this->assertFalse($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));
        $this->assertTrue($cache->has('key3'));
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $result = $cache->deleteMultiple(['key1', 'key3']);

        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
        $this->assertTrue($cache->has('key2'));
        $this->assertFalse($cache->has('key3'));
        $this->assertSame(1, $cache->getSize());
    }

    public function testDeleteMultipleWithNonExistentKeys(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');

        $result = $cache->deleteMultiple(['key1', 'nonexistent']);

        $this->assertTrue($result);
        $this->assertFalse($cache->has('key1'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');

        $this->assertTrue($cache->has('key1'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $cache = new InternalArrayCache();

        $this->assertFalse($cache->has('nonexistent'));
    }

    public function testHasReturnsFalseAfterDelete(): void
    {
        $cache = new InternalArrayCache();

        $cache->set('key1', 'value1');
        $cache->delete('key1');

        $this->assertFalse($cache->has('key1'));
    }
}
