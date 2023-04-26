<?php

declare(strict_types=1);

namespace Acaisia\ByteArray;

use Acaisia\ByteArray\Exception\InvalidByteException;

/**
 * A class to hold an array of bytes
 */
class ByteArray implements \Stringable, \Countable, \Iterator
{
    private int $position = 0;

    private array $array = [];

    private function __construct()
    {
        // Private constructor
        $this->position = 0;
    }

    /**
     * Returns a PHP native array with bytes. Internally, these are integers, but they will always be >=0 and <=255
     * @return int[]
     */
    public function toArray(): array
    {
        return $this->array;
    }

    /**
     * Create a ByteArray from an array. Each element has to be a byte as type <int>
     */
    public static function fromArray(array $array): self
    {
        $self = new self();
        foreach ($array as $value) {
            self::assertValidByte($value);
            $self->array[] = $value;
        }

        return $self;
    }

    public function startsWith(int|string|ByteArray $startsWith): bool
    {
        // If its an int we just check the first one
        if (is_int($startsWith)) {
            if ($this->count() == 0) {
                return false;
            }
            return $this->array[0] == $startsWith;
        }

        // If its a string, we cast to ByteArray
        if (is_string($startsWith)) {
            return $this->startsWith(self::fromString($startsWith));
        }

        $startsWithLength = $startsWith->count();
        if ($startsWithLength == 0) {
            return true; // Well, technically, each array starts with an empty array.. Maybe throw a warning?
        }

        if ($startsWithLength > $this->count()) {
            // If the given is longer than our own, we bail out
            return false;
        }

        // If not we cut out the same length array
        $cut = array_slice($this->array, 0, $startsWithLength);
        return $cut == $startsWith->toArray(); // and return if its the same or not
    }

    public function append(ByteArray $array): self
    {
        foreach ($array->toArray() as $value) {
            $this->array[] = $value;
        }
        return $this;
    }

    /**
     * Create a byte array from string. Assumes every character in the string to be an unsigned char.
     */
    public static function fromString(string $string): self
    {
        $unpacked = unpack('C*', $string);
        if ($unpacked === false) {
            throw new InvalidByteException('Could not unpack string');
        }
        return self::fromArray($unpacked);
    }

    public function __toString(): string
    {
        return pack("C*", ...$this->array);
    }

    /**
     * @throws InvalidByteException if $value is not a valid byte
     */
    private static function assertValidByte($value): void
    {
        if (!is_int($value)) {
            throw new InvalidByteException(
                var_export($value, true) . ' is not a valid byte (must be <int>)'
            );
        }

        if ($value < 0) {
            throw new InvalidByteException(
                var_export($value, true) . ' is not a valid byte (must be >= 0)'
            );
        }

        if ($value > 255) {
            throw new InvalidByteException(
                var_export($value, true) . ' is not a valid byte (must be <= 255)'
            );
        }
    }

    public function count(): int
    {
        return count($this->array);
    }

    // Iterator implementation
    public function rewind(): void {
        $this->position = 0;
    }

    public function current(): int {
        return $this->array[$this->position];
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        ++$this->position;
    }

    public function valid(): bool {
        return isset($this->array[$this->position]);
    }
}
