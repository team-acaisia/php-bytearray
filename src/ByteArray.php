<?php

declare(strict_types=1);

namespace Acaisia\ByteArray;

use Acaisia\ByteArray\Exception\InvalidByteException;

/**
 * A class to hold an array of bytes
 */
class ByteArray implements \Stringable, \Countable //@todo might implement ArrayAccess or Traversable
{
    private $array = [];

    private function __construct()
    {
        // Private constructor
    }

    /**
     * Returns a PHP native array with bytes. Internally, these are integers, but they will always be >=0 and <=254
     * @return byte[]
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
}
