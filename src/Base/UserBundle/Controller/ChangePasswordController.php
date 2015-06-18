<?php

namespace Base\UserBundle\Controller;

use FOS\UserBundle\Controller\ChangePasswordController as BaseController;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\UserBundle\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;

class ChangePasswordController extends BaseController
{

    /**
     * {@inheritDoc}
     */
    public function changePasswordAction()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        $form = $this->container->get('fos_user.change_password.form');
        $formHandler = $this->container->get('fos_user.change_password.form.handler');

        $process = $formHandler->process($user);
        if ($process) {
            $this->setFlash('notice', 'change_password.flash.success');

            return new RedirectResponse($this->getRedirectionUrl($user));
        }

        return $this->container->get('templating')->renderResponse(
            'FOSUserBundle:ChangePassword:changePassword.html.'.$this->container->getParameter('fos_user.template.engine'),
            array('form' => $form->createView())
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('fos_user_profile_edit');
    }
}
