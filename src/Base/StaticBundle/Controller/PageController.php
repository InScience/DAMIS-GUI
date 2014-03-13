<?php

namespace Base\StaticBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Base\StaticBundle\Entity\Page;
use Base\StaticBundle\Form\PageType;
use APY\DataGridBundle\Grid\Source\Entity;
use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;

/**
 * Page controller.
 */
class PageController extends Controller
{
    /**
     * Lists all Page entities.
     *
     * @Route("/pages.html", name="page")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $source = new Entity('BaseStaticBundle:Page');

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');

        $grid->setSource($source);
        $grid->setLimits(25);
        $grid->setNoResultMessage($this->get('translator')->trans('No data'));

        //custom colums config
        $grid->hideColumns('id');

        //add actions column
        $rowAction = new RowAction($this->get('translator')->trans('Edit'), 'page_edit');
        $actionsColumn2 = new ActionsColumn('info_column', $this->get('translator')->trans('Actions'), array($rowAction), "<br/>");
        $grid->addColumn($actionsColumn2);

        return $grid->getGridResponse('BaseStaticBundle::Page\index.html.twig');
    }

    /**
     * Creates a new Page entity.
     *
     * @Route("/pages/", name="page_create")
     * @Method("POST")
     * @Template("BaseStaticBundle:Page:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Page();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $position = $em->getRepository('BaseStaticBundle:Page')->getMaxTextPosition()['max_position']+1;
            if (empty($position)) $position = 1;

            $entity->setPosition($position);

            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully created');

            return $this->redirect($this->generateUrl('page'));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
    * Creates a form to create a Page entity.
    *
    * @param Page $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Page $entity)
    {
        $form = $this->createForm(new PageType(), $entity, array(
            'action' => $this->generateUrl('page_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Page entity.
     *
     * @Route("/pages/new.html", name="page_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Page();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Page entity.
     *
     * @Route("/page/{group}/{slug}.html", name="page_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BaseStaticBundle:Page')->findOneBy(array('slug' => $slug));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }
        $entity->setText(str_replace('<table','<table class="list"', $entity->getText()));
        return array(
            'entity'      => $entity,
        );
    }

    /**
     * Displays a form to edit an existing Page entity.
     *
     * @Route("/pages/{id}/edit.html", name="page_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BaseStaticBundle:Page')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        $log = $em->getRepository('BaseLoggingBundle:EntityLog')->getLogEntriesLimit($entity);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'logTable' => $log,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Page entity.
    *
    * @param Page $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Page $entity)
    {
        $form = $this->createForm(new PageType(), $entity, array(
            'action' => $this->generateUrl('page_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Page entity.
     *
     * @Route("/pages/{id}", name="page_update")
     * @Method("PUT")
     * @Template("BaseStaticBundle:Page:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BaseStaticBundle:Page')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setSlug(null);

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully edited');

            return $this->redirect($this->generateUrl('page_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Page entity.
     *
     * @Route("/pages/{id}", name="page_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('BaseStaticBundle:Page')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Page entity.');
            }

            $em->remove($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Successfully deleted');
        }

        return $this->redirect($this->generateUrl('page'));
    }

    /**
     * Creates a form to delete a Page entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('page_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Change position of shop group text.
     *
     * @Route("/pages/{id}/up", name="page_up")
     * @Method("GET")
     */
    public function upPagePositionAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entityText = $em->getRepository('BaseStaticBundle:Page')->find($id);

        if (!$entityText) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $securityContext = $this->get('security.context');

        if ($entityText->getPosition() > 1) {
            $newPosition = $entityText->getPosition()-1;
            $entityTextSwap = $em->getRepository('BaseStaticBundle:Page')->getNextUpPosition($newPosition);

            if ($entityTextSwap) {
                $entityTextSwap->setPosition($entityText->getPosition());
                $entityText->setPosition($newPosition);

                $em->merge($entityText);
                $em->merge($entityTextSwap);
                $em->flush();
            }
        }

        return $this->redirect($this->generateUrl('page'));
    }

    /**
     * Change position of shop group text.
     *
     * @Route("/pages/{id}/down", name="page_down")
     * @Method("GET")
     */
    public function downPagePositionAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entityText = $em->getRepository('BaseStaticBundle:Page')->find($id);

        if (!$entityText) {
            throw $this->createNotFoundException('Unable to find Page entity.');
        }

        $newPosition = $entityText->getPosition()+1;
        $entityTextSwap = $em->getRepository('BaseStaticBundle:Page')->getNextDownPosition($newPosition);

        if ($entityTextSwap) {
            $entityTextSwap->setPosition($entityText->getPosition());
            $entityText->setPosition($newPosition);

            $em->merge($entityText);
            $em->merge($entityTextSwap);
            $em->flush();

        }

        return $this->redirect($this->generateUrl('page'));
    }

}
