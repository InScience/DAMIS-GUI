<?php
// src/Service/TwigTemplatingEngine.php
namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TwigTemplatingEngine
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function supports($name)
    {
        // Support all templates
        return true;
    }

    public function render($name, array $parameters = [])
    {
        return $this->twig->render($name, $parameters);
    }

    public function renderResponse($view, array $parameters = [], Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }

    public function exists($name)
    {
        return $this->twig->getLoader()->exists($name);
    }
    
    // Proxy any other methods to Twig
    public function __call($method, $args)
    {
        return call_user_func_array([$this->twig, $method], $args);
    }
}