<?php

namespace Base\UserBundle\Controller;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException; // Added this use statement

class RegistrationController extends AbstractController
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher, private readonly FactoryInterface $formFactory, private readonly UserManagerInterface $userManager, private readonly TokenStorageInterface $tokenStorage)
    {
    }

    #[Route('/register/', name: 'fos_user_registration_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_SUCCESS);

                $this->userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }

                $this->eventDispatcher->dispatch(
                    new FilterUserResponseEvent($user, $request, $response),
                    FOSUserEvents::REGISTRATION_COMPLETED
                );

                return $response;
            }

            $event = new FormEvent($form, $request);
            $this->eventDispatcher->dispatch($event, FOSUserEvents::REGISTRATION_FAILURE);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('@BaseUser/Registration/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/register/confirmed', name: 'fos_user_registration_confirmed', methods: ['GET'])]
    public function confirmed(): Response
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('@FOSUser/Registration/confirmed.html.twig', [
            'user' => $user,
            // 'targetUrl' => $this->generateUrl('base_main_index'),
            'targetUrl' => $this->generateUrl('base_main_default_index'),
        ]);
    }
}
