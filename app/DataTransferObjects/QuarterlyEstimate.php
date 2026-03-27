<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class QuarterlyEstimate
{
    public function __construct(
        public readonly int $quarter,
        public readonly string $deadline,
        public readonly float $amountDue,
        public readonly bool $isPast,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'quarter' => $this->quarter,
            'deadline' => $this->deadline,
            'amountDue' => $this->amountDue,
            'isPast' => $this->isPast,
        ];
    }
}
