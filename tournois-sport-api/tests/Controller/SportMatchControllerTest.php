<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\SportMatch;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Attribute\Route;

final class SportMatchControllerTest extends WebTestCase
{
    public function testPlayerCannotUpdateOpponentScore(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $chloe = $em->getRepository(User::class)->findOneBy(['emailAddress' => 'chloe@example.com']);
        $match = $em->getRepository(SportMatch::class)->findOneBy(['status' => SportMatch::STATUS_EN_ATTENTE]);

        self::assertInstanceOf(User::class, $chloe);
        self::assertInstanceOf(SportMatch::class, $match);

        $client->loginUser($chloe);
        $client->request(
            'PUT',
            sprintf('/api/tournaments/%d/sport-matchs/%d', $match->getTournament()?->getId(), $match->getId()),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['scorePlayer2' => 5], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
    }
}