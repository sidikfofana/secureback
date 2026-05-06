<?php

namespace App\Enum;

enum ProjectStatus: string
{
    case PLANNED = 'planned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';

    public static function fromInt(int|string $value): self
    {
        return match ((string)$value) {
            '1', 'planned' => self::PLANNED,
            '2', 'in_progress' => self::IN_PROGRESS,
            '3', 'completed' => self::COMPLETED,
            '4', 'on_hold' => self::ON_HOLD,
            '5', 'cancelled' => self::CANCELLED,
            default => throw new \InvalidArgumentException("\"$value\" is not a valid ProjectStatus"),
        };
    }
}

