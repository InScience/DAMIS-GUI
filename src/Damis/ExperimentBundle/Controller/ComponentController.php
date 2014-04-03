<?php

namespace Damis\ExperimentBundle\Controller;

use Base\ConvertBundle\Helpers\ReadFile;
use Damis\ExperimentBundle\Entity\Component;
use Damis\ExperimentBundle\Entity\Parameter;
use Damis\ExperimentBundle\Helpers\Experiment as ExperimentHelper;
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
        /** @var $component Component */
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
        $data = json_decode($request->get('data'));
        $form_data = [];
        if($request->getMethod() != 'POST' && !empty($data)) {
            $parametersIds = [];
            $values = [];
            foreach($data as $parameter) {
                $parametersIds[$parameter->id] = $parameter->id;
                $values[$parameter->id] = $parameter->value;
            }

            /** @var $helper ExperimentHelper */
            $helper = $this->get('experiment');
            $parameters = $helper->getParameters($parametersIds);

            /** @var $param Parameter */
            foreach($parameters as $param) {
                $form->get($param->getSlug())->submit($values[$param->getId()]);
                $form_data[$param->getSlug()] = $values[$param->getId()];
            }


        }

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
                        'form_data' => $requestParams[$formParam]
                    ]
                );
                $response = new Response(json_encode( array("html" => $html,  'componentId' => $id) ));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        }
        $parameters = $this->getDoctrine()
            ->getManager()
            ->getRepository('DamisExperimentBundle:Parameter')
            ->findBy(['component' => $id]);

        $response = [];

        foreach($parameters as $parameter) {
            if($parameter->getSlug())
                $response[$parameter->getId()] = $form->get($parameter->getSlug())->getData();

        }

        $html = $this->renderView(
            'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
            [
                'form' => $form->createView(),
                'response' => json_encode($response),
                'form_data' => $form_data
            ]
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
        $data = json_decode($request->get('data'));
        if($request->get('data') && !empty($data)) {
            $id = json_decode($request->get('data'))[0]->value;
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
        } else {
            if($id == 'undefined')
                $id = null;
            else
                $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
        }

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
            $entities, $this->get('request')->query->get('page', 1), 8);
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
                    if(mb_strtolower($row[0]) != '@data'){
                        if(strpos(mb_strtolower($row[key($row)]), '@attribute') === 0 ){
                            $attr = explode(' ', $row[key($row)]);
                            if(trim(strtoupper($attr[1])) != 'CLASS')
                                $attributes[] =  array('type' => $attr[2], 'name' => $attr[1]);
                            else {
                                if($attr[2] == 'string')
                                    $attributes[] =  array('type' => $attr[2], 'name' => $attr[1]);
                                else {
                                    $_row = implode(', ', $row);
                                    $_attr = explode('{', $_row);
                                    $__attr = explode('}', $_attr[1]);
                                    $attributes[] = array('type' => $__attr[0], 'name' => 'CLASS');
                                }
                            }
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
     * Technical information action
     *
     * @Route("/experiment/component/{id}/technical/information.html", name="technical_information", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function technicalInformationAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = null;
        $message = '';
        $runtime = '';
         if($id == 'undefined')
            $id = null;
        else {
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
            if($request->isMethod('POST')) {
                return $this->redirect($this->generateUrl('convert_' . $request->get('format'), array('id' => $id)));
            } else {
                $workflow = $em->getRepository('DamisEntitiesBundle:Workflowtask')
                    ->findOneBy(array('experiment' => $request->get('experimentId'), 'taskBox' => $request->get('taskBox')));
                $runtime = $workflow->getExecutionTime();
                $message = $workflow->getMessage();
            }
        }
        return array(
            'id' => $id,
            'file' => $entity,
            'message' => $message,
            'runtime' => $runtime
        );
    }
}
