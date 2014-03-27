<?php

namespace Damis\ExperimentBundle\Controller;

use Base\ConvertBundle\Helpers\ReadFile;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends Controller
{
    /**
     * Component info
     *
     * @Route("/experiment/component/{id}/info.html", name="component", options={"expose" = true})
     * @Template()
     */
    public function componentAction($id)
    {
        $parameters = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Parameter')
            ->findBy(['component' => $id]);

        return [
            'parameters' => $parameters
        ];
    }

    /**
     * Component form
     *
     * @Route("/experiment/component/{id}/form.html", name="component_form", options={"expose" = true})
     * @Method({"GET", "POST"})
     */
    public function componentFormAction(Request $request, $id)
    {
        $component = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Component')
            ->findOneBy(['id' => $id]);

        $options = ['choices' => [], 'class' => []];
        $datasetId = $request->get('dataset_id');
        if($datasetId > 0) {
            /** @var $dataset \Damis\DatasetsBundle\Entity\Dataset */
            $dataset = $this->getDoctrine()
                ->getManager()
                ->getRepository('DamisDatasetsBundle:Dataset')
                ->findOneBy(['datasetId' => $datasetId]);
            $helper = new ReadFile();
            $attributes = $helper->getAttributes('.' . $dataset->getFilePath());
            $class = $helper->getClassAttr('.' . $dataset->getFilePath());
            $options['choices'] = $attributes;
            $options['class'] = $class;
        }

        $formType = 'Damis\ExperimentBundle\Form\Type\\' . $component->getFormType() . 'Type';
        $form = $this->createForm(new $formType(), $options);

        if($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $parameters = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('DamisExperimentBundle:Parameter')
                    ->findBy(['component' => $id]);

                $requestParams = $request->request->all();
                $response = [];

                $formParam = strtolower($component->getFormType()) . '_type';

                foreach($parameters as $parameter) {
                    if(isset($requestParams[$formParam][$parameter->getSlug()]))
                        $response[$parameter->getId()] = $requestParams[$formParam][$parameter->getSlug()];

                }
                $html = $this->renderView(
                    'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
                    [
                        'form' => $form->createView(),
                        'response' => json_encode($response),
                    ]
                );
                $response = new Response(json_encode( array("html" => $html,  'componentId' => $id) ));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        }
        $html = $this->renderView(
            'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
            ['form' => $form->createView()]
        );
        $response = new Response(json_encode( array("html" => $html,  'componentId' => $id) ));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    /**
     * User datasets list window
     *
     * @Route("/experiment/component/existingFile.html", name="existing_file", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function existingFileAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $sort = $request->get('order_by');
        $id  = $request->get('id');
        $entity = null;
        if($id == 'undefined')
            $id = null;
        else
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);

        $user = $this->get('security.context')->getToken()->getUser();
        if($sort == 'titleASC')
            $entities = $em->getRepository('DamisDatasetsBundle:Dataset')
                ->getUserDatasets($user, array('title' => 'ASC'));
        elseif($sort == 'titleDESC')
            $entities = $em->getRepository('DamisDatasetsBundle:Dataset')
                ->getUserDatasets($user, array('title' => 'DESC'));
        elseif($sort == 'createdASC')
            $entities = $em->getRepository('DamisDatasetsBundle:Dataset')
                ->getUserDatasets($user, array('created' => 'ASC'));
        elseif($sort == 'createdDESC')
            $entities = $em->getRepository('DamisDatasetsBundle:Dataset')
                ->getUserDatasets($user, array('created' => 'DESC'));
        else
            $entities = $em->getRepository('DamisDatasetsBundle:Dataset')->getUserDatasets($user);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities, $this->get('request')->query->get('page', 1), 15);
        return array(
            'entities' => $pagination,
            'selected' => $id,
            'file' => $entity
        );
    }
    /**
     * Matrix view
     *
     * @Route("/experiment/component/{id}/matrixView.html", name="matrix_view", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function matrixViewAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = null;
        $attributes = array();
        $rows = array();
        if($id == 'undefined')
            $id = null;
        else {
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
            if($request->isMethod('POST')) {
                return $this->redirect($this->generateUrl('convert_' . $request->get('format'), array('id' => $id)));
            } else {
                $helper = new ReadFile();
                $rows = $helper->getRows('.' . $entity->getFilePath(), 'arff');
                foreach($rows as $key => $row){
                    if($row[0] != '@data'){
                        if(strpos($row[key($row)], '@attribute') === 0){
                            $attr = explode(' ', $row[key($row)]);
                                $attributes[] =  array('type' => $attr[2], 'name' => $attr[1]);
                        }
                        unset($rows[$key]);
                    } else {
                        unset($rows[$key]);
                        break;
                    }
                }
            }
        }
        return array(
            'id' => $id,
            'attributes' => $attributes,
            'rows' => array_slice($rows, 0, 1000)
        );
    }

    /**
     * Matrix view
     *
     * @Route("/experiment/component/{id}/technical/information.html", name="technical_information", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function technicalInformationAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = null;
        if($id == 'undefined')
            $id = null;
        else {
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
            if($request->isMethod('POST')) {
                return $this->redirect($this->generateUrl('convert_' . $request->get('format'), array('id' => $id)));
            } else {
           //@todo surasti workflow task pagal dataset
            }
        }
        return array(
            'id' => $id,
            'file' => $entity
        );
    }

}
