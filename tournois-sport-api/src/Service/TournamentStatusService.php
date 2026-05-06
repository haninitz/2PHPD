<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tournament;
use DateTimeImmutable;

final class TournamentStatusService
{
    public const STATUS_A_VENIR = 'a_venir';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_TERMINE = 'termine';

    public function compute(Tournament $tournament, ?DateTimeImmutable $today = null): string
    {
        if ($today === null) {
            $today = new DateTimeImmutable('today');
        }

        if ($tournament->getStartDate() === null || $tournament->getEndDate() === null) {
            return self::STATUS_A_VENIR;
        }

        if ($today < $tournament->getStartDate()) {
            return self::STATUS_A_VENIR;
        }

        if ($today > $tournament->getEndDate()) {
            return self::STATUS_TERMINE;
        }

        return self::STATUS_EN_COURS;
    }
}