<?php

namespace Base\UserBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Base\UserBundle\Entity\User;
use Base\UserBundle\Form\UserType;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Grid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * User controller.
 */
#[Route('/users')]
class UserController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    /**
     * Lists all User entities.
     */
    #[Route('.html', name: 'users')]
    public function index(TranslatorInterface $translator, Grid $grid)
    {
        $source = new Entity(User::class);

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid->setSource($source);

        $grid->hideColumns(['id', 'salt', 'password', 'lastLogin', 'confirmationToken', 'passwordRequestedAt', 'enabled', 'credentialsExpired', 'expired', 'usernameCanonical', 'emailCanonical', 'userId', 'registeredAt', 'organisation']);
        $grid->setLimits([25, 50, 100]);
        $grid->setNoResultMessage($translator->trans('No data', [], 'messages'));


        /* @var $column \APY\DataGridBundle\Grid\Column\Column */
        if ($grid->hasColumn('name')) {
            $column = $grid->getColumn('name');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setTitle($translator->trans('form.name', [], 'FOSUserBundle'));
        }

        if ($grid->hasColumn('surname')) {
            $column = $grid->getColumn('surname');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setTitle($translator->trans('form.surname', [], 'FOSUserBundle'));
        }

        if ($grid->hasColumn('username')) {
            $column = $grid->getColumn('username');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setTitle($translator->trans('form.username', [], 'FOSUserBundle'));
        }

        if ($grid->hasColumn('email')) {
            $column = $grid->getColumn('email');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setTitle($translator->trans('form.email', [], 'FOSUserBundle'));
        }

        if ($grid->hasColumn('roles')) {
            $column = $grid->getColumn('roles');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setTitle($translator->trans('form.role', [], 'FOSUserBundle'));
            $column->setValues([
                'ROLE_ADMIN' => $translator->trans('admin.role_admin', [], 'FOSUserBundle'),
                'ROLE_CONFIRMED' => $translator->trans('admin.role_confirmed', [], 'FOSUserBundle'),
                'ROLE_USER' => $translator->trans('admin.role_user', [], 'FOSUserBundle')
            ]);
        }

        if ($grid->hasColumn('locked')) {
            $column = $grid->getColumn('locked');
            $column->setTitle($translator->trans('form.locked', [], 'FOSUserBundle'));
            $column->setValues([
                true => $translator->trans('admin.positive', [], 'FOSUserBundle'),
                false => $translator->trans('admin.negative', [], 'FOSUserBundle')
            ]);
        }

        // Add actions column with Edit button
        $rowAction = new RowAction($translator->trans('Edit', [], 'messages'), 'user_edit');
        $actionsColumn = new ActionsColumn('actions_column', $translator->trans('Actions', [], 'messages'));
        $actionsColumn->setRowActions([$rowAction]);
        $grid->addColumn($actionsColumn);

        // isReadyForRedirect() must be called AFTER all columns are configured
        $grid->isReadyForRedirect();

        return $grid->getGridResponse('@BaseUser/User/index.html.twig');
    }

    /**
     * Displays a form to edit an existing User entity.
     */
    #[Route('/{id}/edit.html', name: 'user_edit', methods: ['GET'])]
    public function edit($id): Response
    {
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(User::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('@BaseUser/User/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ]);
    }

    /**
     * Creates a form to edit a User entity.
     *
     * @param User $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(User $entity): Form
    {
        $form = $this->createForm(UserType::class, $entity, [
            'action' => $this->generateUrl('user_update', ['id' => $entity->getId()]),
            'method' => 'POST',
        ]);

        return $form;
    }

    /**
     * Edits an existing User entity.
     */
    #[Route('/{id}/update.html', name: 'user_update', methods: ['POST'])]
    public function update(Request $request, $id, SessionInterface $session): Response
    {
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(User::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();

            $session->getFlashBag()->add('success', 'profile.flash.updated');

            return $this->redirectToRoute('users');
        }

        return $this->render('@BaseUser/User/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
        ]);
    }
}