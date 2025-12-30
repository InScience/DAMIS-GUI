<?php

namespace Base\MainBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class TemplatingWrapper
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function supports(string $name): bool
    {
        // Always return true for twig templates
        return true;
    }

    public function render(string $view, array $parameters = []): string
    {
        return $this->twig->render($view, $parameters);
    }

    public function renderResponse(string $view, array $parameters = [], Response $response = null): Response
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }

    public function exists(string $name): bool
    {
        return $this->twig->getLoader()->exists($name);
    }
}