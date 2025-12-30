<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/styled-login', name: 'styled_login')]
    public function styledLogin(): Response
    {
        return $this->render('styled_login.html.twig');
    }
}
