<?php declare(strict_types=1);

namespace Kuria\Iterable;

use Kuria\DevMeta\Test;

class IterableHelperTest extends Test
{
    /**
     * @dataProvider provideIterablesForArrayConversion
     */
    function testShouldConvertToArray(iterable $iterable, array $expectedResult)
    {
        $this->assertSame($expectedResult, IterableHelper::toArray($iterable));
    }

    function provideIterablesForArrayConversion()
    {
        return [
            // iterable, expectedResult
            'empty array' => [
                [],
                [],
            ],

            'empty iterable' => [
                new \ArrayObject(),
                [],
            ],

            'simple array' => [
                [1, 2, 3],
                [1, 2, 3],
            ],

            'simple iterable' => [
                new \ArrayObject([4, 5, 6]),
                [4, 5, 6],
            ],

            'array with custom keys' => [
                ['foo' => 'bar', 'baz' => 'qux'],
                ['foo' => 'bar', 'baz' => 'qux'],
            ],

            'iterable with custom keys' => [
                new \ArrayObject(['lorem' => 'ipsum', 'dolor' => 'sit']),
                ['lorem' => 'ipsum', 'dolor' => 'sit'],
            ],

            'iterable with duplicate keys' => [
                (function () {
                    yield 'a' => 1;
                    yield 'b' => 2;
                    yield 'a' => 3;
                })(),
                ['a' => 3, 'b' => 2],
            ],
        ];
    }

    /**
     * @dataProvider provideIterablesForListConversion
     */
    function testShouldConvertToList(iterable $iterable, array $expectedResult)
    {
        $this->assertSame($expectedResult, IterableHelper::toList($iterable));
    }

    function provideIterablesForListConversion()
    {
        return [
            // iterable, expectedResult
            'empty array' => [
                [],
                [],
            ],

            'empty iterable' => [
                new \ArrayObject(),
                [],
            ],

            'simple array' => [
                [1, 2, 3],
                [1, 2, 3],
            ],

            'simple iterable' => [
                new \ArrayObject([4, 5, 6]),
                [4, 5, 6],
            ],

            'array with custom keys' => [
                ['foo' => 'bar', 'baz' => 'qux'],
                ['bar', 'qux'],
            ],

            'iterable with custom keys' => [
                new \ArrayObject(['lorem' => 'ipsum', 'dolor' => 'sit']),
                ['ipsum', 'sit'],
            ],

            'iterable with duplicate keys' => [
                (function () {
                    yield 'a' => 1;
                    yield 'b' => 2;
                    yield 'a' => 3;
                })(),
                [1, 2, 3],
            ],
        ];
    }
}
