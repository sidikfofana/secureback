<?php

namespace App\Enum;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public static function fromInt(int|string $value): self
    {
        return match ((string)$value) {
            '1', 'todo' => self::TODO,
            '2', 'in_progress' => self::IN_PROGRESS,
            '3', 'review' => self::REVIEW,
            '4', 'done' => self::DONE,
            default => throw new \InvalidArgumentException("\"$value\" is not a valid TaskStatus"),
        };
    }
}
