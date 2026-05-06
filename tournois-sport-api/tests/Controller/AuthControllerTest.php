<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    public function testRegisterWorks(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'lastName' => 'Martin',
                'firstName' => 'Eva',
                'username' => 'eva',
                'emailAddress' => 'eva@example.com',
                'password' => 'secret123',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);
    }
}