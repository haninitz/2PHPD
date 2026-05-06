<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Tournament;
use App\Service\TournamentStatusService;
use PHPUnit\Framework\TestCase;

final class TournamentStatusServiceTest extends TestCase
{
    public function testComputesStatusCorrectly(): void
    {
        $service = new TournamentStatusService();

        $tournament = (new Tournament())
            ->setTournamentName('Test')
            ->setDescription('Test')
            ->setSport('Tennis')
            ->setMaxParticipants(8)
            ->setStartDate(new \DateTimeImmutable('2026-05-10'))
            ->setEndDate(new \DateTimeImmutable('2026-05-12'));

        self::assertSame('a_venir', $service->compute($tournament, new \DateTimeImmutable('2026-05-09')));
        self::assertSame('en_cours', $service->compute($tournament, new \DateTimeImmutable('2026-05-10')));
        self::assertSame('termine', $service->compute($tournament, new \DateTimeImmutable('2026-05-13')));
    }
}