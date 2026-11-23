<?php

namespace App\Tests\Functional;

/** AWStats pages for administrators; DAMIS_AWSTATS_DIR points to tests/fixtures/awstats (phpunit.xml.dist). */
class StatisticsTest extends DamisWebTestCase
{
    public function testAnonymousUserIsSentToLogin(): void
    {
        $this->client->request('GET', '/admin/statistics/en/2026-01/awstats.damis.html');

        self::assertResponseRedirects('http://localhost/login');
    }

    public function testUserWithoutAdminRoleIsForbidden(): void
    {
        $this->client->loginUser($this->createUser('user'));

        $this->client->request('GET', '/admin/statistics/en/2026-01/awstats.damis.html');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdministratorSeesLatestMonth(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/statistics');
        self::assertResponseRedirects('/admin/statistics/lt/2026-01');

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/statistics/en/2026-01/awstats.damis.html');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('fixture statistics page', $this->client->getInternalResponse()->getContent());
    }

    public function testAdministratorGetsOnlyStatisticsFiles(): void
    {
        $this->client->loginUser($this->createAdmin());

        $this->client->request('GET', '/admin/statistics/en/2025-12');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/statistics/en/2026-01/awstats.damis.missing.html');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/statistics/en/2026-01/..%2F..%2F..%2Fphpunit.xml.dist');
        self::assertResponseStatusCodeSame(404);
    }
}
