<?php declare(strict_types=1);

namespace Kuria\Iterable\Iterator;

use Kuria\DevMeta\Test;
use PHPUnit\Framework\MockObject\MockObject;

class CachingIteratorTest extends Test
{
    /**
     * @dataProvider provideTraversables
     */
    function testShouldCachePairs(\Traversable $traversable, array $expectedData)
    {
        $cachingIterator = new CachingIterator($traversable);

        // repeated iteration
        $this->assertSame($expectedData, iterator_to_array($cachingIterator));
        $this->assertSame($expectedData, iterator_to_array($cachingIterator));

        // manual iteration
        $cachingIterator->rewind();

        foreach ($expectedData as $key => $current) {
            $this->assertTrue($cachingIterator->valid());
            $this->assertSame($key, $cachingIterator->key());
            $this->assertSame($current, $cachingIterator->current());

            $cachingIterator->next();
        }

        // assert end state
        $this->assertFalse($cachingIterator->valid());
        $this->assertNull($cachingIterator->key());
        $this->assertNull($cachingIterator->current());

        // next() at the end should do nothing
        $cachingIterator->next();
        $this->assertFalse($cachingIterator->valid());
        $this->assertNull($cachingIterator->key());
        $this->assertNull($cachingIterator->current());
    }

    function provideTraversables(): array
    {
        $object = new \stdClass();

        $traversables = [
            // traversable, expectedData
            'iterator' => [
                (static function () use ($object) {
                    yield 'foo' => 'bar';
                    yield 'baz' => 'qux';
                    yield 'quux' => 123;
                    yield 'quuz' => $object;
                })(),
                [
                    'foo' => 'bar',
                    'baz' => 'qux',
                    'quux' => 123,
                    'quuz' => $object,
                ],
            ],

            'iterator aggregate' => [
                new class implements \IteratorAggregate {
                    function getIterator()
                    {
                        return new class implements \IteratorAggregate {
                            function getIterator()
                            {
                                    return new \ArrayIterator(['foo' => 'bar', 'baz' => 'qux']);
                            }
                        };
                    }
                },
                ['foo' => 'bar', 'baz' => 'qux'],
            ],
        ];

        return $traversables;
    }

    function testShouldCachePairsWithDuplicateKeys()
    {
        $cachingIterator = new CachingIterator((function () {
            yield 'key' => 'a';
            yield 'key' => 'b';
            yield 'key' => 'c';
        })());

        $keys = [];
        $values = [];

        foreach ($cachingIterator as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->assertSame(['key', 'key', 'key'], $keys);
        $this->assertSame(['a', 'b', 'c'], $values);
    }

    /**
     * @requires SimpleXML
     */
    function testShouldWrapTraversable()
    {
        // SimpleXmlElement is an example of a class that implements \Traversable but not \Iterator
        // userland code cannot implement \Traversable directly

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <item>foo</item>
    <item>bar</item>
    <item>baz</item>
</root>
XML;

        $traversable = (new \SimpleXMLElement($xml))->item;

        $this->assertNotInstanceOf(\Iterator::class, $traversable);

        $cachingIterator = new CachingIterator($traversable);

        // assert values while ignoring keys (SimpleXMLElement always returns "item")
        $expectedData = [
            $traversable[0],
            $traversable[1],
            $traversable[2],
        ];

        $this->assertEquals($expectedData, iterator_to_array($cachingIterator, false));
        $this->assertEquals($expectedData, iterator_to_array($cachingIterator, false));
    }

    function testShouldHandleEmptyTraversable()
    {
        $cachingIterator = new CachingIterator(new \ArrayIterator([]));

        $this->assertFalse($cachingIterator->valid());
        $this->assertNull($cachingIterator->key());
        $this->assertNull($cachingIterator->current());
    }

    function testShouldNotRewindIteratorIfDisabled()
    {
        $iteratorMock = $this->createMock(\Iterator::class);

        $iteratorMock->expects($this->never())
            ->method('rewind');

        $iteratorMock->expects($this->once())
            ->method('valid')
            ->willReturn(false);

        new CachingIterator($iteratorMock, false);
    }

    function testShouldHandleRewindMidIteration()
    {
        $cachingIterator = new CachingIterator($this->createUnrewindableIterator());

        $this->assertIteratorState($cachingIterator, 'foo', 'bar');

        $cachingIterator->next();

        $this->assertTrue($cachingIterator->valid());
        $this->assertSame('baz', $cachingIterator->key());
        $this->assertSame('qux', $cachingIterator->current());

        $cachingIterator->rewind();
    }

    function testShouldCountPairs()
    {
        $cachingIterator = new CachingIterator($this->createUnrewindableIterator());

        $this->assertCount(4, $cachingIterator);

        $this->assertSame(
            [
                'foo' => 'bar',
                'baz' => 'qux',
                'quux' => 'quuz',
                'lorem' => 'ipsum',
            ],
            iterator_to_array($cachingIterator)
        );
    }

    function testShouldCallCountOfCountableIterator()
    {
        /** @var \Traversable|MockObject $countableIteratorMock */
        $countableIteratorMock = $this->createMock([\Iterator::class, \Countable::class]);

        $countableIteratorMock->method('valid')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $countableIteratorMock->expects($this->once())
            ->method('count')
            ->willReturn(123);

        $cachingIterator = new CachingIterator($countableIteratorMock);

        $this->assertCount(123, $cachingIterator);
    }

    private function createUnrewindableIterator(): \Iterator
    {
        yield 'foo' => 'bar';
        yield 'baz' => 'qux';
        yield 'quux' => 'quuz';
        yield 'lorem' => 'ipsum';
    }

    private function assertIteratorState(\Iterator $iterator, $expectedKey, $expectedCurrent): void
    {
        $this->assertTrue($iterator->valid());
        $this->assertSame($expectedKey, $iterator->key());
        $this->assertSame($expectedCurrent, $iterator->current());
    }
}
