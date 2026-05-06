<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminAccessTest extends WebTestCase
{
    public function testDashboardIsForbiddenForNormalUser(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['emailAddress' => 'alice@example.com']);

        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);
        $client->request('GET', '/admin/dashboard');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDashboardIsOkForAdmin(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $admin = $em->getRepository(User::class)->findOneBy(['emailAddress' => 'admin@example.com']);

        self::assertInstanceOf(User::class, $admin);

        $client->loginUser($admin);
        $client->request('GET', '/admin/dashboard');

        self::assertResponseIsSuccessful();
    }
}