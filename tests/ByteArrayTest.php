<?php

declare(strict_types=1);

namespace Acaisia\ByteArray\Tests;

use Acaisia\ByteArray\ByteArray;
use Acaisia\ByteArray\Exception\InvalidByteException;

class ByteArrayTest extends AbstractTestCase
{
    /**
     * @dataProvider provideValidByteArrays
     */
    public function testValidByteArrays(array $array) : void
    {
        $byteArray = ByteArray::fromArray($array);
        $this->assertEquals($array, $byteArray->toArray());
    }

    public static function provideValidByteArrays() : array
    {
        return [
            [[0]],
            [[]],
            [[1, 2, 3, 4, 5]],
            [[0, 0, 0, 0, 0]],
            [[255, 254, 0, 0, 255]],
            [[255, 254, 0, 127, 255]],
        ];
    }

    /**
     * @dataProvider provideInvalidByteArrays
     */
    public function testInvalidByteArrays(array $array, string $expectedErrorMessage) : void
    {
        $this->expectException(InvalidByteException::class);
        $this->expectExceptionMessage($expectedErrorMessage);
        ByteArray::fromArray($array);
    }

    public static function provideInvalidByteArrays() : array
    {
        return [
            [[-1], '-1 is not a valid byte (must be >= 0)'],
            [[-191282397], '-191282397 is not a valid byte (must be >= 0)'],

            [[23489723], '23489723 is not a valid byte (must be <= 255)'],
            [[256], '256 is not a valid byte (must be <= 255)'],

            [['some string'], "'some string' is not a valid byte (must be <int>)"],
            [[new \stdClass()], "(object) array(\n) is not a valid byte (must be <int>)"],
            [[null], "NULL is not a valid byte (must be <int>)"],
            [[0, 1, 2, 3, 0.59438, 28, 40], "0.59438 is not a valid byte (must be <int>)"],
        ];
    }

    /**
     * @dataProvider provideString
     */
    public function testString(string $string, array $array) : void
    {
        $byteArray = ByteArray::fromString($string);
        $this->assertEquals(
            $array,
            $byteArray->toArray()
        );
        $this->assertSame($string, (string) $byteArray);
    }

    public static function provideString() : array
    {
        return [
            ['', []], // Empty string = Empty result
            [chr(0), [0]], // null byte = 0x00
            [str_repeat(chr(13), 4), [13, 13, 13, 13]], // A list of bytes
            ['Hello World!', [72, 101, 108, 108, 111, 32, 87, 111, 114, 108, 100, 33]], // ASCII characters
            ['é', [195, 169]], // Check some UTF-8 encoding, should be 2 bytes
            ['🚀', [240, 159, 154, 128]], // This emoji should be 4 bytes (unicode)
        ];
    }

    /**
     * @dataProvider provideAppend
     */
    public function testAppend(ByteArray $current, ByteArray $appended, ByteArray $expected) : void
    {
        $this->assertEquals($current->append($appended), $expected);
    }

    public static function provideAppend() : array
    {
        return [
            [ByteArray::fromArray([]), ByteArray::fromArray([]), ByteArray::fromArray([])], // 2x empty = empty
            [ByteArray::fromArray([123]), ByteArray::fromArray([]), ByteArray::fromArray([123])],
            [ByteArray::fromArray([]), ByteArray::fromArray([234]), ByteArray::fromArray([234])],

            [ByteArray::fromArray([123]), ByteArray::fromArray([234]), ByteArray::fromArray([123, 234])],

            [ByteArray::fromString('🚀'), ByteArray::fromString('🐸'), ByteArray::fromString('🚀🐸')],
        ];
    }

    /**
     * @dataProvider provideCount
     */
    public function testCount(ByteArray $array, int $expectedCount) : void
    {
        $this->assertSame($expectedCount, $array->count());
        $this->assertSame($expectedCount, count($array));
    }

    public static function provideCount() : array
    {
        return [
            [ByteArray::fromArray([]), 0],
            [ByteArray::fromArray([123]), 1],
            [ByteArray::fromString('🚀'), 4],
        ];
    }

    public function testIterator(): void
    {
        $expected = ['f0', '9f', '9a', '80'];

        $i = 0;
        foreach (ByteArray::fromString('🚀') as $byte) {
            $this->assertSame($expected[$i++], dechex($byte));
        }

        $array = ByteArray::fromString('🚀🐸');
        $this->assertSame(0, $array->key());
        $this->assertTrue($array->valid());
        $this->assertSame(240, $array->current()); // First byte
        $array->next();
        $this->assertTrue($array->valid());
        $this->assertSame(159, $array->current()); // Second byte
        for ($i = 0; $i < 6; $i++) { // Iterate the next 6 bytes
            $array->next();
            $this->assertTrue($array->valid());
        }

        $array->next(); // Now we should be out of range of the array
        $this->assertFalse($array->valid());
        $this->assertSame(8, $array->key());

        // But we can keep iterating
        $array->next();
        $this->assertFalse($array->valid());
        $this->assertSame(9, $array->key());

        // And rewind
        $array->rewind();
        $this->assertSame(0, $array->key());
        $this->assertTrue($array->valid());
        $this->assertSame(240, $array->current()); // First byte
    }

    /**
     * @dataProvider provideStartsWith
     */
    public function testStartsWith(ByteArray $given, $check, bool $expected): void
    {
        $this->assertSame($expected, $given->startsWith($check));
    }

    public static function provideStartsWith(): array
    {
        return [
            [ByteArray::fromArray([]), ByteArray::fromArray([]), true],
            [ByteArray::fromArray([]), '', true],
            [ByteArray::fromArray([]), 0, false],
            [ByteArray::fromArray([0]), 0, true],
            [ByteArray::fromArray([0, 1, 2, 3]), 0, true],

            [ByteArray::fromString('Testing?'), 'Testing?', true],
            [ByteArray::fromString('Testing?'), 'Testing????', false],
            [ByteArray::fromString('Testing?'), 'T', true],

            [ByteArray::fromString('Testing?'), 0x54, true],
            [ByteArray::fromString('Testing?'), 84, true],

            [ByteArray::fromString('🚀🐸'), ByteArray::fromString('🚀🐸'), true],
            [ByteArray::fromString('🚀🐸'), ByteArray::fromString('🚀'), true],
            [ByteArray::fromString('🚀🐸'), '🚀', true],
        ];
    }
}
