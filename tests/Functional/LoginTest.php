<?php

namespace App\Tests\Functional;

class LoginTest extends DamisWebTestCase
{
    public function testLoginWithValidCsrfToken(): void
    {
        $user = $this->createUser('login');

        $this->loginWithForm($user->getUsername(), 'Test-password-1');

        self::assertResponseRedirects('http://localhost/');
        $this->client->request('GET', '/experiment/new.html');
        self::assertResponseIsSuccessful();
    }

    public function testLoginWithoutCsrfTokenIsRejected(): void
    {
        $user = $this->createUser('login_csrf');

        $this->loginWithForm($user->getUsername(), 'Test-password-1', false);

        self::assertResponseRedirects('http://localhost/login');
        $this->client->request('GET', '/experiment/new.html');
        self::assertResponseRedirects('http://localhost/login');
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $user = $this->createUser('login_wrong');

        $this->loginWithForm($user->getUsername(), 'not-the-password');

        self::assertResponseRedirects('http://localhost/login');
        $this->client->request('GET', '/experiment/new.html');
        self::assertResponseRedirects('http://localhost/login');
    }
}
