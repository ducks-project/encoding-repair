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

namespace Ducks\Component\EncodingRepair\Tests\phpunit;

use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

final class ArrayCacheTest extends TestCase
{
    public function testGetUnsetsExpiredEntry(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1', 1);
        $this->assertSame('value1', $cache->get('key1'));

        \sleep(2);

        $result = $cache->get('key1', 'default');

        $this->assertSame('default', $result);
        $this->assertNull($cache->get('key1'));
    }

    public function testDeleteRemovesKey(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1');
        $this->assertSame('value1', $cache->get('key1'));

        $result = $cache->delete('key1');

        $this->assertTrue($result);
        $this->assertNull($cache->get('key1'));
    }

    public function testDeleteNonExistentKeyReturnsTrue(): void
    {
        $cache = new ArrayCache();

        $result = $cache->delete('nonexistent');

        $this->assertTrue($result);
    }

    public function testGetMultipleReturnsValues(): void
    {
        $cache = new ArrayCache();

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
        $cache = new ArrayCache();

        $cache->set('key1', 'value1');

        $result = $cache->getMultiple(['key1', 'key2'], 'default');

        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'default',
        ], $result);
    }

    public function testGetMultipleSkipsExpiredEntries(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1', 1);
        $cache->set('key2', 'value2');

        \sleep(2);

        $result = $cache->getMultiple(['key1', 'key2'], 'default');

        $this->assertSame([
            'key1' => 'default',
            'key2' => 'value2',
        ], $result);
    }

    public function testSetMultipleSetsAllValues(): void
    {
        $cache = new ArrayCache();

        $result = $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $this->assertTrue($result);
        $this->assertSame('value1', $cache->get('key1'));
        $this->assertSame('value2', $cache->get('key2'));
        $this->assertSame('value3', $cache->get('key3'));
    }

    public function testSetMultipleWithTtl(): void
    {
        $cache = new ArrayCache();

        $cache->setMultiple([
            'key1' => 'value1',
            'key2' => 'value2',
        ], 1);

        $this->assertSame('value1', $cache->get('key1'));

        \sleep(2);

        $this->assertNull($cache->get('key1'));
        $this->assertNull($cache->get('key2'));
    }

    public function testDeleteMultipleRemovesAllKeys(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        $cache->set('key3', 'value3');

        $result = $cache->deleteMultiple(['key1', 'key3']);

        $this->assertTrue($result);
        $this->assertNull($cache->get('key1'));
        $this->assertSame('value2', $cache->get('key2'));
        $this->assertNull($cache->get('key3'));
    }

    public function testDeleteMultipleWithNonExistentKeys(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1');

        $result = $cache->deleteMultiple(['key1', 'nonexistent']);

        $this->assertTrue($result);
        $this->assertNull($cache->get('key1'));
    }

    public function testHasReturnsTrueForExistingKey(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1');

        $this->assertTrue($cache->has('key1'));
    }

    public function testHasReturnsFalseForNonExistentKey(): void
    {
        $cache = new ArrayCache();

        $this->assertFalse($cache->has('nonexistent'));
    }

    public function testHasReturnsFalseForExpiredKey(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1', 1);

        \sleep(2);

        $this->assertFalse($cache->has('key1'));
    }

    public function testCalculateExpiryWithNull(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1', null);

        \sleep(2);

        $this->assertSame('value1', $cache->get('key1'));
    }

    public function testCalculateExpiryWithDateInterval(): void
    {
        $cache = new ArrayCache();

        $interval = new \DateInterval('PT2S');
        $cache->set('key1', 'value1', $interval);

        $this->assertSame('value1', $cache->get('key1'));

        \sleep(3);

        $this->assertNull($cache->get('key1'));
    }

    public function testCalculateExpiryWithInteger(): void
    {
        $cache = new ArrayCache();

        $cache->set('key1', 'value1', 2);

        $this->assertSame('value1', $cache->get('key1'));

        \sleep(3);

        $this->assertNull($cache->get('key1'));
    }
}
