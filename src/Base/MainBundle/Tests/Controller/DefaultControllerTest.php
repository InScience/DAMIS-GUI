<?php

namespace Base\MainBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request(Request::METHOD_GET, '/');

        $this->assertTrue($client->getResponse()->isSuccessful());
    }
}
