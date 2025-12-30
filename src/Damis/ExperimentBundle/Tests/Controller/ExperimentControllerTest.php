<?php

namespace Damis\ExperimentBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class ExperimentControllerTest extends WebTestCase
{
    private $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * This is the most basic and important test for a secured page.
     * It proves that an anonymous user cannot access it and is correctly
     * redirected to the login page.
     */
    public function testNewExperimentPageIsProtected()
    {
        $this->client->request('GET', '/experiment/new.html');

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->assertTrue(
            $this->client->getResponse()->isRedirect('http://localhost/login'),
            'A guest user should be redirected to the login page.'
        );
    }

    /**
     * This is the "money" test. It simulates a full user login and then
     * verifies access to the secured page. This tests the entire chain:
     * routing, firewall, login form, user provider, and the target controller.
     */
    public function testLoggedInUserCanAccessNewExperimentPage()
        {
            $userManager = $this->client->getContainer()->get('fos_user.user_manager');
            $user = $userManager->findUserByUsername('admin1');
            if (!$user) {
                $user = $userManager->createUser();
                $user->setUsername('admin1');
                $user->setEmail('admin1@test.com');
                $user->setPlainPassword('123');
                $user->setEnabled(true);
                $user->setRoles(['ROLE_CONFIRMED']);
                $userManager->updateUser($user);
            }
            $this->client->followRedirects(true);
            $username = 'admin1';
            $password = '123';

            $crawler = $this->client->request('GET', '/login');

            $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "The login page should load successfully.");

            $form = $crawler->selectButton('_submit')->form([
                '_username' => $username,
                '_password' => $password,
            ]);
            $this->client->submit($form);
            $crawler = $this->client->request('GET', '/experiment/new.html');

            $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
            $this->assertEquals('/experiment/new.html', $this->client->getRequest()->getPathInfo());
            $this->assertStringContainsString(
                'Eksperimento pavadinimas:',
                $this->client->getResponse()->getContent(),
                "The page should contain the text 'Eksperimentai:'"
            );
            $this->assertStringContainsString(
                'Pradėti eksperimentą nuo:',
                $this->client->getResponse()->getContent(),
                "The page should contain the text 'Pradėti eksperimentą nuo:'"
            );
        }
}