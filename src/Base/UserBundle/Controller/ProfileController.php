<?php

namespace Base\UserBundle\Controller;

use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    private $formFactory;
    private $userManager;

    public function __construct(FactoryInterface $formFactory, UserManagerInterface $userManager)
    {
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
    }

    /**
     * Show the user profile
     */
    #[Route('/profile/', name: 'fos_user_profile_show', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('@FOSUser/Profile/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Edit user profile
     */
    #[Route('/profile/edit', name: 'fos_user_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->userManager->updateUser($user);

            $this->addFlash('success', 'profile.flash.updated');

            return new RedirectResponse($this->generateUrl('fos_user_profile_edit'));
        }

        return $this->render('@FOSUser/Profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}