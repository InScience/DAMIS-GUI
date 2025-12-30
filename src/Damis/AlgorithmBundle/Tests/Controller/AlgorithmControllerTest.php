<?php

namespace Damis\AlgorithmBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlgorithmControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request(Request::METHOD_GET, '/algorithm/list');

        //$this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }
}
