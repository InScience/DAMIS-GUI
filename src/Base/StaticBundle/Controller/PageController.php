<?php

namespace Base\StaticBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Base\StaticBundle\Entity\Page;
use Base\StaticBundle\Form\PageType;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Grid;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Page controller.
 */
class PageController extends AbstractController
{
     public function __construct(private readonly ManagerRegistry $doctrine) {}

    /**
     * Show for static menu
     */
    public function staticMenu($groupName, Request $request)
    {
        $em = $this->doctrine->getManager(); // <-- Use injected doctrine

        $entities = $em->getRepository(Page::class)->findBy(
            ['groupName' => $groupName, 'language' => $request->getLocale()],
            ['position' => 'ASC']
        );

        return $this->render('@BaseStatic/staticMenu.html.twig', ['pages' => $entities]);
    }

    /**
     * Show for static info
     */
    public function staticInfo($groupName, Request $request)
    {
        $em = $this->doctrine->getManager();

        $entities = $em->getRepository(Page::class)->findBy(
            ['groupName' => $groupName, 'language' => $request->getLocale()],
            ['position' => 'ASC']
        );

        return $this->render('@BaseStatic/staticInfo.html.twig', ['pages' => $entities]);
    }

    /**
     * Lists all Page entities.
     */
    #[Route("/pages.html", name: "page")]
    public function index(Grid $grid, TranslatorInterface $translator, Request $request)
    {
        $source = new Entity(Page::class);

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $tableAlias = $source->getTableAlias();
        $source->manipulateQuery(
            function ($query) use ($tableAlias) {
                $query->resetDQLPart('orderBy');
                $query->addOrderBy($tableAlias.'.groupName', 'ASC');
                $query->addOrderBy($tableAlias.'.position', 'ASC');
            }
        );

        $grid->setSource($source);
        $grid->setLimits([25]);
        $grid->setNoResultMessage($translator->trans('No data'));

        $grid->hideColumns(['id']);

        /* @var $column \APY\DataGridBundle\Grid\Column\Column */
        if ($grid->hasColumn('title')) {
            $column = $grid->getColumn('title');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setSortable(false);
            $column->setTitle($translator->trans('form.title', [], 'StaticBundle'));
        }

        if ($grid->hasColumn('groupName')) {
            $column = $grid->getColumn('groupName');
            $column->setOperators(['like']);
            $column->setOperatorsVisible(false);
            $column->setDefaultOperator('like');
            $column->setSortable(false);
            $column->setTitle($translator->trans('form.group', [], 'StaticBundle'));
        }

        if ($grid->hasColumn('language')) {
            $column = $grid->getColumn('language');
            $column->manipulateRenderCell(
                function ($value, $row, $router) {
                    if ($value instanceof \Base\StaticBundle\Entity\LanguageEnum) {
                        return $value->value;
                    }
                    return $value;
                }
            );
            $column->setTitle($translator->trans('form.language', [], 'StaticBundle'));
        }

        $rowAction = new RowAction($translator->trans('Edit'), 'page_edit');
        $actionsColumn2 = new ActionsColumn('info_column', $translator->trans('Actions'));
        $actionsColumn2->setRowActions([$rowAction]);
        $grid->addColumn($actionsColumn2);

        return $grid->getGridResponse('@BaseStatic/Page/index.html.twig');
    }

    /**
     * Creates a new Page entity.
     */
    #[Route("/pages/", name: "page_create", methods: ["POST"])]
    public function create(Request $request, SessionInterface $session)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();

            $maxPosResult = $em->getRepository(Page::class)->getMaxTextPosition($entity->getGroupName());
            $position = ($maxPosResult && isset($maxPosResult['max_position'])) ? $maxPosResult['max_position'] + 1 : 1;

            $entity->setPosition($position);

            $em->persist($entity);
            $em->flush();

            $session->getFlashBag()->add('notice', 'form.created');

            return $this->redirectToRoute('page');
        }

        return $this->render('@BaseStatic/Page/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
    * Creates a form to create a Page entity.
    */
    private function createCreateForm(Page $entity): Form
    {
        $form = $this->createForm(PageType::class, $entity, [
            'action' => $this->generateUrl('page_create'),
            'method' => 'POST'
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Create']);

        return $form;
    }

    /**
     * Displays a form to create a new Page entity.
     */
    #[Route("/pages/new.html", name: "page_new", methods: ["GET"])]
    public function new()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return $this->render('@BaseStatic/Page/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    /**
     * Finds and displays a Page entity.
     */
    #[Route("/page/{slug}.html", name: "page_show", methods: ["GET"])]
    public function show($slug)
    {
        $em = $this->doctrine->getManager(); // <-- Use injected doctrine

        $entity = $em->getRepository(Page::class)->findOneBy(['slug' => $slug]);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }
        $entity->setText(str_replace('<table', '<table class="list"', (string) $entity->getText()));

        return $this->render('@BaseStatic/Page/show.html.twig', ['entity' => $entity]);
    }

    /**
     * Displays a form to edit an existing Page entity.
     */
    #[Route("/pages/{id}/edit.html", name: "page_edit", methods: ["GET"])]
    public function edit($id)
    {
        $em = $this->doctrine->getManager();

        $entity = $em->getRepository(Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('@BaseStatic/Page/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        ]);
    }

    /**
    * Creates a form to edit a Page entity.
    */
    private function createEditForm(Page $entity): Form
    {
        $form = $this->createForm(PageType::class, $entity, [
            'action' => $this->generateUrl('page_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);

        $form->add('submit', SubmitType::class, ['label' => 'Update']);

        return $form;
    }

    /**
     * Edits an existing Page entity.
     */
    #[Route("/pages/{id}", name: "page_update", methods: ["PUT"])]
    public function update(Request $request, $id, SessionInterface $session)
    {
        $em = $this->doctrine->getManager();

        $entity = $em->getRepository(Page::class)->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            $em->flush();

            $session->getFlashBag()->add('notice', 'form.updated');

            return $this->redirectToRoute('page_edit', ['id' => $id]);
        }

        return $this->render('@BaseStatic/Page/edit.html.twig', [
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        ]);
    }

    /**
     * Deletes a Page entity.
     */
    #[Route("/pages/{id}", name: "page_delete", methods: ["DELETE"])]
    public function delete(Request $request, $id, SessionInterface $session)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager(); // <-- Use injected doctrine
            $entity = $em->getRepository(Page::class)->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Page entity.');
            }

            $em->remove($entity);
            $em->flush();

            $session->getFlashBag()->add('notice', 'form.deleted');
        } else {
             $session->getFlashBag()->add('error', 'Invalid delete request.');
        }


        return $this->redirectToRoute('page');
    }

    /**
    * Creates a form to delete a Page entity by id.
    * (Internal method, no route)
    */
    private function createDeleteForm(mixed $id): Form
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('page_delete', ['id' => $id]))
            ->setMethod(Request::METHOD_DELETE)
            ->add('submit', SubmitType::class, ['label' => 'Delete', 'attr' => ['class' => 'btn btn-danger']])
            ->getForm()
        ;
    }

    /**
     * Move Page position up.
     */
    #[Route("/pages/{id}/up", name: "page_up", methods: ["GET"])]
    public function upPagePosition($id)
    {
        $em = $this->doctrine->getManager(); // <-- Use injected doctrine

        $entityText = $em->getRepository(Page::class)->find($id);

        if (!$entityText) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        if ($entityText->getPosition() > 1) {
            $currentPosition = $entityText->getPosition();
            $newPosition = $currentPosition - 1;

            // Find the entity currently at the new position within the same group
            $entityTextSwap = $em->getRepository(Page::class)->findOneBy([
                'position' => $newPosition,
                'groupName' => $entityText->getGroupName()
            ]);

            if ($entityTextSwap) {
                // Swap positions
                $entityTextSwap->setPosition($currentPosition);
                $entityText->setPosition($newPosition);

                $em->persist($entityText);
                $em->persist($entityTextSwap);
                $em->flush();
            } else {
                 $entityText->setPosition($newPosition);
                 $em->persist($entityText);
                 $em->flush();
            }
        }

        return $this->redirectToRoute('page');
    }

    /**
     * Move Page position down.
     */
    #[Route("/pages/{id}/down", name: "page_down", methods: ["GET"])]
    public function downPagePosition($id)
    {
        $em = $this->doctrine->getManager();

        $entityText = $em->getRepository(Page::class)->find($id);

        if (!$entityText) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $currentPosition = $entityText->getPosition();
        $newPosition = $currentPosition + 1;

        $entityTextSwap = $em->getRepository(Page::class)->findOneBy([
            'position' => $newPosition,
            'groupName' => $entityText->getGroupName()
        ]);


        if ($entityTextSwap) {
            $entityTextSwap->setPosition($currentPosition);
            $entityText->setPosition($newPosition);

            $em->persist($entityText);
            $em->persist($entityTextSwap);
            $em->flush();
        } else {
        }


        return $this->redirectToRoute('page');
    }
}