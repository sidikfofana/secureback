<?php

namespace App\Enum;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public static function fromInt(int|string $value): self
    {
        return match ((string)$value) {
            '1', 'low' => self::LOW,
            '2', 'medium' => self::MEDIUM,
            '3', 'high' => self::HIGH,
            '4', 'urgent' => self::URGENT,
            default => throw new \InvalidArgumentException("\"$value\" is not a valid TaskPriority"),
        };
    }
}
