<?php

namespace Damis\DatasetsBundle\Controller;

use Damis\DatasetsBundle\Form\Type\DatasetType;
use Damis\DatasetsBundle\Entity\Dataset;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Datasets controller.
 *
 * @Route("/datasets")
 */
class DatasetsController extends Controller
{
    /**
     * User datasets list window
     *
     * @Route("/list.html", name="datasets_list")
     * @Template()
     */
    public function listAction()
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('DamisDatasetsBundle:Dataset')->getUserDatasets($user);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities, $this->get('request')->query->get('page', 1), 15);
        return array(
            'entities' => $pagination
        );
    }

    /**
     * Upload new dataset
     *
     * @Route("/new.html", name="datasets_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Dataset();
        $form = $this->createForm(new DatasetType(), $entity);
        return array(
            'form' => $form->createView()
        );
    }
    /**
     * Create new dataset
     *
     * @Route("/create.html", name="datasets_create")
     * @Method("POST")
     * @Template("DamisDatasetsBundle:Datasets:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Dataset();
        $form = $this->createForm(new DatasetType(), $entity);
        $form->submit($request);
        $user = $this->get('security.context')->getToken()->getUser();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setDatasetCreated(time());
            $entity->setUserId($user);
            $entity->setDatasetIsMidas(false);
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Dataset successfully uploaded!');
            return $this->redirect($this->generateUrl('datasets_list'));
        }
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * Edit dataset
     *
     * @Route("/{id}/edit.html", name="datasets_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
        $form = $this->createForm(new DatasetType(), null);
        $form->get('datasetTitle')->setData($entity->getDatasetTitle());
        $form->get('datasetDescription')->setData($entity->getDatasetDescription());
        return array(
            'form' => $form->createView(),
            'id' => $entity->getDatasetId()
        );
    }

    /**
     * Update dataset
     *
     * @Route("/{id}/update.html", name="datasets_update")
     * @Method("POST")
     * @Template("DamisDatasetsBundle:Datasets:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
        $form = $this->createForm(new DatasetType(), null);
        $form->get('datasetTitle')->setData($entity->getDatasetTitle());
        $form->get('datasetDescription')->setData($entity->getDatasetDescription());
        $form->submit($request);
        if ($form->isValid()) {
            $data = $request->get('datasets_newtype');
            $entity->setDatasetUpdated(time());
            $entity->setDatasetTitle($data['datasetTitle']);
            $entity->setDatasetDescription($data['datasetDescription']);
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Dataset successfully updated!');
            return $this->redirect($this->generateUrl('datasets_list'));
        }
        return array(
            'form' => $form->createView()
        );
    }


    /**
     * Dataset upload component form
     *
     * @Route("/upload.html", name="dataset_upload")
     * @Template()
     */
    public function uploadAction()
    {
        return [];
    }
}
