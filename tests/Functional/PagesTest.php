<?php

namespace App\Tests\Functional;

class PagesTest extends DamisWebTestCase
{
    /** @dataProvider publicPages */
    public function testPublicPageOpens(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public static function publicPages(): iterable
    {
        yield 'home' => ['/'];
        yield 'login' => ['/login'];
        yield 'registration' => ['/register/'];
        yield 'password reset request' => ['/resetting/request'];
    }

    /** @dataProvider protectedPages */
    public function testProtectedPageRedirectsAnonymousUserToLogin(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseRedirects('http://localhost/login');
    }

    public static function protectedPages(): iterable
    {
        yield 'new experiment' => ['/experiment/new.html'];
        yield 'datasets' => ['/datasets/list.html'];
        yield 'profile' => ['/profile/change-password'];
        yield 'statistics' => ['/admin/statistics'];
    }

    public function testConfirmedUserOpensExperimentEditor(): void
    {
        $this->client->loginUser($this->createUser('editor'));

        $this->client->request('GET', '/experiment/new.html');

        self::assertResponseIsSuccessful();
    }

    public function testConfirmedUserOpensDatasetList(): void
    {
        $this->client->loginUser($this->createUser('lister'));

        $this->client->request('GET', '/datasets/list.html');

        self::assertResponseIsSuccessful();
    }
}
