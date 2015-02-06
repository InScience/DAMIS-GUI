<?php

namespace Damis\ExperimentBundle\Controller;

use Base\ConvertBundle\Helpers\ReadFile;
use CURLFile;
use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Entity\Component;
use Damis\ExperimentBundle\Entity\Parameter;
use Damis\ExperimentBundle\Helpers\Experiment as ExperimentHelper;
use Guzzle\Http\Client;
use PHPExcel_IOFactory;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

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
        $formData = [];
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
                $formData[$param->getSlug()] = $values[$param->getId()];
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

                /** @var $parameter \Damis\ExperimentBundle\Entity\Parameter */
                foreach($parameters as $parameter)
                    if($parameter->getConnectionType()->getId() == 3)
                        if(isset($requestParams[$formParam][$parameter->getSlug()]))
                            $response[$parameter->getId()] = $requestParams[$formParam][$parameter->getSlug()];


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


        $response = $formData;
        if($request->getMethod() != 'POST' && empty($data)) {
            $parameters = $this->getDoctrine()
                ->getManager()
                ->getRepository('DamisExperimentBundle:Parameter')
                ->findBy(['component' => $id]);

            /** @var $parameter \Damis\ExperimentBundle\Entity\Parameter */
            foreach($parameters as $parameter)
                if($parameter->getSlug() && $parameter->getConnectionType()->getId() == 3)
                    $response[$parameter->getId()] = $form->get($parameter->getSlug())->getData();

        }

        $html = $this->renderView(
            'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
            [
                'form' => $form->createView(),
                'response' => json_encode($response),
                'form_data' => $formData
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
     * User midas datasets list window
     *
     * @Route("/experiment/component/existingMidasFile.html", name="existing_midas_file", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function existingMidasFileAction(Request $request)
    {
        $client = new Client($this->container->getParameter('midas_url'));
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        if($session->has('sessionToken'))
            $sessionToken = $session->get('sessionToken');
        else {
            echo('Prašome prisijungti prie midas');
            die;
        }
    //    $sessionToken = 'g47n5tpirgmhom6k0n015kmgp2';
        if($request->getMethod() == "POST"){
            $data = json_decode(json_decode($request->get('data'), true)[0]['value'], true);
            //$req = $client->get('/web/action/file-explorer/file?path='.$data['path'].'&name='.$data['name'].'&repositoryType=research&type=FILE&authorization='.$sessionToken);
            $req = $client->get('/web/action/file-explorer/file?path='.$data['path'].'&name='.$data['name'].'&idCSV='.$data['idCSV'].'&authorization='.$sessionToken);
            try {
                $body = $req->send()->getBody(true);
                $file = new Dataset();
                $file->setDatasetTitle(basename($data['name']));
                $file->setDatasetCreated(time());
                $user = $this->get('security.context')->getToken()->getUser();
                $file->setUserId($user);
                $file->setDatasetIsMidas(true);
                $temp_file = $this->container->getParameter("kernel.cache_dir") . '/../'. time() . $data['name'];
                $em->persist($file);
                $em->flush();
                $fp = fopen($temp_file,"w");
                fwrite($fp, $body);
                fclose($fp);

                $file2 = new File($temp_file);

                $ref_class = new ReflectionClass('Damis\DatasetsBundle\Entity\Dataset');
                $mapping = $this->container->get('iphp.filestore.mapping.factory')->getMappingFromField($file, $ref_class, 'file');
                $file_data = $this->container->get('iphp.filestore.filestorage.file_system')->upload($mapping, $file2);

                $org_file_name = basename($data['name']);
                $file_data['originalName'] = $org_file_name;

                $file->setFile($file_data);
                $em->persist($file);
                $em->flush();
                unlink($temp_file);
                $response = $this->uploadArff($file->getDatasetId());
                if(!$response)
                    return false;
            } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error fetching file', array(), 'DatasetsBundle'));
                return false;
            }
                return [
                    'file' => $file,
                    'files' => null
                ];
        }
        if(isset($request->query->all()['dataset_url'])) {
            $data = json_decode($request->query->all()['dataset_url']);
            if ($request->query->all() && !empty($data)) {
                $datasetId = $data[0]->value;
                $em = $this->getDoctrine()->getManager();
                $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($datasetId);
                return [
                    'file' => $dataset,
                    'files' => null
                ];
            }
        }
        $page = ($request->get('page')) ? $request->get('page') : 1;
        $path = ($request->get('path')) ? $request->get('path') : '';
        $uuid = ($request->get('uuid')) ? $request->get('uuid') : 'research';
        $id = $request->get('id');

        $data = json_decode($request->get('data'));
        if($request->get('data') && !empty($data) && $request->get('edit') != 1){
            $id = json_decode($request->get('data'))[0]->value;
            $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);
            return [
                'file' => $dataset,
                'files' => null
            ];
        }
		// Default path
		if (!$path) {
			$files = array('details' => 
				array('folderDetailsList' =>
					array(
						0 => 
							array (
								 'name' =>  $this->get('translator')->trans('Published research'),
								 'path' => 'publishedResearch',
								 'type' => 'RESEARCH',
								 'modifyDate' => time() * 1000,
								 'page' => 0,
								 'uuid' => 'publishedResearch',
								 'resourceId'	=> ''
							),
						1 => 
							array (
								 'name' => $this->get('translator')->trans('Not published research'),
								 'path' => 'research',
								 'type' => 'RESEARCH',
								 'modifyDate' => time() * 1000,
								 'page' => 0,
								 'uuid' => 'research',
								 'resourceId'	=> ''
							)
					)
				));
			return array(
				'file' => null,
				'files' => $files,
				'page' => 0,
				'pageCount' => 1,
				'totalFiles' => 0,
				'previous' => 0,
				'next' => 0,
				'path' => $path,
				'uuid' => '',
				'selected' => 0
			);			
		}
		/// Else if $path is selected        
        $post = array(
            //'path' => $path,
            'page' => $page,
            'pageSize' => 10,
            //'extensions' => array('txt', 'tab', 'csv', 'xls', 'xlsx', 'arff', 'zip'),  // can be added also zip
            //'repositoryType' => 'research'
            'uuid' => $uuid
        );
        $files = [];
        $req = $client->post('/web/action/research/folders',
            array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($post));
        try {
            $response = $req->send();
            if($response->getStatusCode() == 200)
                $files = json_decode($response->getBody(true), true);
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $req = $client->post('/web/action/authentication/session/' . $sessionToken . '/check', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), array($post));
            try {
                 $req->send()->getBody(true);
            } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                var_dump('Error! ' . $e->getMessage()); die;
            }
        }

        $pageCount = $files['details']['pageCount'];
		// Remove bad files
		$extensions = array('txt', 'tab', 'csv', 'xls', 'xlsx', 'arff', 'zip');
		$tmpItems = $files['details']['folderDetailsList'];
		foreach ($tmpItems as $nr => $item) {
			if ($item['type'] == 'FILE' && !in_array(pathinfo($item['name'], PATHINFO_EXTENSION), $extensions)) {
				unset($files['details']['folderDetailsList'][$nr]);
			}
		}
        return array(
            'file' => null,
            'files' => $files,
            'page' => $page,
            'pageCount' => $pageCount,
            'previous' => $page - 1,
            'next' => $page + 1,
            'path' => $path,
            'uuid' => $uuid,
            'selected' => $id
        );
    }


    /**
     * User midas datasets list
     *
     * @Route("/experiment/component/existingMidasFolders.html", name="existing_midas_folders", options={"expose" = true})
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function midasFoldersAction(Request $request)
   {
        $client = new Client($this->container->getParameter('midas_url'));

        $session = $request->getSession();
        $sessionToken = '';
        if($session->has('sessionToken'))
            $sessionToken = $session->get('sessionToken');
        else {
            echo('Prašome prisijungti prie midas');
            die;
        }
        $page = ($request->get('page')) ? $request->get('page') : 1;
        $path = ($request->get('path')) ? $request->get('path') : '';
        $uuid = ($request->get('uuid')) ? $request->get('uuid') : 'research';
        $id = $request->get('id');

        $data = json_decode($request->get('data'));
        if($request->get('data') && !empty($data) && $request->get('edit') != 1){
            $id = json_decode($request->get('data'))[0]->value;
            $path = json_decode($id, true)['path'];
            $page = json_decode($id, true)['page'];

            $folders = explode('/', $path);
            $count = count($folders);
            $path = '';
            foreach($folders as $key => $p){
                if($key < $count - 1)
                    $path .= $p . '/';
            }
        }
		// Default path
		if (!$path) {
			$files = array('details' => 
				array('folderDetailsList' =>
					array(
						0 => 
							array (
								 'name' =>  $this->get('translator')->trans('Published research'),
								 'path' => 'publishedResearch',
								 'type' => 'RESEARCH',
								 'modifyDate' => time() * 1000,
								 'page' => 0,
								 'uuid' => 'publishedResearch',
								 'resourceId'	=> ''
							),
						1 => 
							array (
								 'name' => $this->get('translator')->trans('Not published research'),
								 'path' => 'research',
								 'type' => 'RESEARCH',
								 'modifyDate' => time() * 1000,
								 'page' => 0,
								 'uuid' => 'research',
								 'resourceId'	=> ''
							)
					)
				));
			return array(
				'files' => $files,
				'page' => 0,
				'pageCount' => 1,
				'totalFiles' => 0,
				'previous' => 0,
				'next' => 0,
				'path' => $path,
				'uuid' => '',
				'selected' => 0
			);			
		}
		/// Else if $path is selected   		
        $post = array(
            //'path' => $path,
            'page' => $page,
            'pageSize' => 10,
            //'extensions' => array('txt', 'tab', 'csv', 'xls', 'xlsx', 'arff', 'zip'),
            //'repositoryType' => 'research'
            'uuid' => $uuid
        );
        $files = [];
        
        $req = $client->post('/web/action/research/folders',
            array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($post));
        try {
            $response = $req->send();
            if($response->getStatusCode() == 200)
                $files = json_decode($response->getBody(true), true);
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $req = $client->post('/web/action/authentication/session/' . $sessionToken . '/check', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), array($post));
            try {
                $req->send()->getBody(true);
            } catch (\Guzzle\Http\Exception\BadResponseException $e) {
				/* @TODO remove var_dump */
                var_dump('Error! ' . $e->getMessage()); die;
            }
        }
        if(isset($files['details']))
            $pageCount = $files['details']['pageCount'];
        else
            $pageCount = 0;
        return array(
            'files' => $files,
            'page' => $page,
            'pageCount' => $pageCount,
            'previous' => $page - 1,
            'next' => $page + 1,
            'path' => $path,
            'uuid' => $uuid,
            'selected' => $id
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
                if($request->get('dst') == 'user-computer')
                    return $this->redirect($this->generateUrl('convert_' . $request->get('format'), array('id' => $id)));
                else if ($request->get('dst') == 'midas') {
                    /** @var Response $response2 */
                    $response2 = $this->forward('BaseConvertBundle:Convert:ConvertTo'. ucfirst($request->get('format')), array(
                        'id'  => $id,
                    ));
                    if($request->get('format') == 'xls' || $request->get('format') == 'xlsx') {
                        $temp_file = $response2->getContent();
                    } else {
                        $temp_file = $this->container->getParameter("kernel.cache_dir") . '/../' . time() . $id;
                        $fp = fopen($temp_file, "w");
                        fwrite($fp, $response2->getContent());
                        fclose($fp);
                    }
                    $client = new Client($this->container->getParameter('midas_url'));
                    $session = $this->get('request')->getSession();
                    if($session->has('sessionToken'))
                        $sessionToken = $session->get('sessionToken');
                    else {
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error uploading file', array(), 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }
                  //  $sessionToken = 'e8tbeefhjt455e4kpbbo02o4vp';
                    $post = array(
                        'name' =>  preg_replace('/\\.[^.\\s]{3,4}$/', '', $entity->getFile()['originalName']). $id . '.'.$request->get('format'),
                        //'path' => json_decode($request->get('path'), true)['path'],
                        //'repositoryType' => 'research',
                        'parentFolderId' => json_decode($request->get('path'), true)['idCSV'],
                        'size' => $entity->getFile()['size']
                    );
                    $req = $client->post('/web/action/file-explorer/file/init', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($post));

                    try {
                        $response = json_decode($req->send()->getBody(true), true);
                        if($response['type'] == 'error'){
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans($response["msgCode"], array(), 'DatasetsBundle'));
                            return $this->redirect($request->headers->get('referer'));
                        }

                        $fileId = $response['file']['id'];
                        $header = array('Content-Type: multipart/form-data', 'Authorization:' . $sessionToken);

                        $file = new CURLFile($temp_file, $response2->headers->get('content-type'), preg_replace('/\\.[^.\\s]{3,4}$/', '', $entity->getFile()['originalName']).$id. '.'.$request->get('format'));

                        $fields = array('slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1);

                        $resource = curl_init();
                        curl_setopt($resource, CURLOPT_URL, $this->container->getParameter('midas_url') . '/web/action/file-explorer/file/slice');
                        curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
                        curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($resource, CURLOPT_POST, 1);
                        curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

                        $result = curl_exec($resource);

                        curl_close($resource);
                        unlink($temp_file);
                        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('File uploaded successfully', array(), 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error uploading file', array(), 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }
                }
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
        /** @var Dataset $entity */
        if($id == 'undefined')
            $id = null;
        else
            $entity = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($id);

        if($request->isMethod('POST')) {
            if($request->get('dst') == 'user-computer')
                return $this->redirect($this->generateUrl('convert_' . $request->get('format'), array('id' => $id)));
            else if ($request->get('dst') == 'midas') {
                /** @var Response $response2 */
                $response2 = $this->forward('BaseConvertBundle:Convert:ConvertTo'. ucfirst($request->get('format')), array(
                    'id'  => $id,
                    'midas' => 1
                ));
                if($request->get('format') == 'xls' || $request->get('format') == 'xlsx') {
                    $temp_file = $response2->getContent();
                } else {
                    $temp_file = $this->container->getParameter("kernel.cache_dir") . '/../' . time() . $id;
                    $fp = fopen($temp_file, "w");
                    fwrite($fp, $response2->getContent());
                    fclose($fp);
                }
                $client = new Client($this->container->getParameter('midas_url'));
                $session = $this->get('request')->getSession();
                if($session->has('sessionToken'))
                    $sessionToken = $session->get('sessionToken');
                else {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error uploading file', array(), 'DatasetsBundle'));
                     return $this->redirect($request->headers->get('referer'));
                }
              //  $sessionToken = 'e8tbeefhjt455e4kpbbo02o4vp';
                $post = array(
                    'name' =>  preg_replace('/\\.[^.\\s]{3,4}$/', '', $entity->getFile()['originalName']). $id . '.'.$request->get('format'),
                    //'path' => json_decode($request->get('path'), true)['path'],
                    //'repositoryType' => 'research',
                    'parentFolderId' => json_decode($request->get('path'), true)['idCSV'],
                    'size' => $entity->getFile()['size']
                );
                $req = $client->post('/web/action/file-explorer/file/init', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($post));

                try {
                    $response = json_decode($req->send()->getBody(true), true);
                    if($response['type'] == 'error'){
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans($response["msgCode"], array(), 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }

                    $fileId = $response['file']['id'];
                    $header = array('Content-Type: multipart/form-data', 'Authorization:' . $sessionToken);

                    $file = new CURLFile($temp_file, $response2->headers->get('content-type'), preg_replace('/\\.[^.\\s]{3,4}$/', '', $entity->getFile()['originalName']).$id. '.'.$request->get('format'));

                    $fields = array('slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1);

                    $resource = curl_init();
                    curl_setopt($resource, CURLOPT_URL, $this->container->getParameter('midas_url') . '/web/action/file-explorer/file/slice');
                    curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($resource, CURLOPT_POST, 1);
                    curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

                    $result = curl_exec($resource);

                    curl_close($resource);
                    unlink($temp_file);
                    $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('File uploaded successfully', array(), 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));
                } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error uploading file', array(), 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));
                }
            }
        } else {
            $workflow = $em->getRepository('DamisEntitiesBundle:Workflowtask')
                ->findOneBy(array('experiment' => $request->get('experimentId'), 'taskBox' => $request->get('taskBox')));
            if($workflow){
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

    /**
     * When uploading csv/txt/tab/xls/xlsx types to arff
     * convert it and save
     *
     * @param String $id
     * @return boolean
     */
    public function uploadArff($id)
    {
        $memoryLimit = ini_get('memory_limit');
        $suffix = '';
        sscanf ($memoryLimit, '%u%c', $number, $suffix);
        if (isset ($suffix))
        {
            $number = $number * pow (1024, strpos (' KMG', $suffix));
        }
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity){
            $format = explode('.', $entity->getFile()['fileName']);
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'zip'){
                $zip = new ZipArchive();
                $res = $zip->open('./assets' . $entity->getFile()['fileName']);
                $name = $zip->getNameIndex(0);
                if($zip->numFiles > 1){
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', array(), 'DatasetsBundle'));
                    return false;
                }

                if($res === true){
                    $path = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '/'));
                    $zip->extractTo('.' . $path, $name);
                    $zip->close();
                    $format = explode('.', $name);
                    $format = $format[count($format)-1];
                    $fileReader = new ReadFile();
                    if ($format == 'arff'){
                        $dir = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '.'));
                        $entity->setFilePath($dir . '.arff');
                        $rows = $fileReader->getRows('.' . $entity->getFilePath() , $format);
                        if($rows === false){
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                            $em->remove($entity);
                            $em->flush();
                            unlink('.' . $path . '/' . $name);
                            return false;
                        }
                        unset($rows);
                        $em->persist($entity);
                        $em->flush();
                        rename ( '.' . $path . '/' . $name , '.' . $dir . '.arff');
                        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
                        return true;
                    }
                    elseif($format == 'txt' || $format == 'tab' || $format == 'csv'){
                        $rows = $fileReader->getRows('.' . $path . '/' . $name , $format);
                        if($rows === false){
                            $em->remove($entity);
                            $em->flush();
                            unlink('.' . $path . '/' . $name);
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', array(), 'DatasetsBundle'));
                            return false;
                        }
                        unlink('.' . $path . '/' . $name);
                    } elseif($format == 'xls' || $format == 'xlsx'){
                        $objPHPExcel = PHPExcel_IOFactory::load('.' . $path . '/' . $name);
                        $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                        array_unshift($rows, null);
                        unlink('.' . $path . '/' . $name);
                        unset($rows[0]);
                    } else{
                        $em->remove($entity);
                        $em->flush();
                        unlink('.' . $path . '/' . $name);
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', array(), 'DatasetsBundle'));
                        return false;
                    }
                }
            }
            elseif ($format == 'arff'){
                $entity->setFilePath($entity->getFile()['path']);
                if(memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number){
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    return false;
                }
                unset($rows);
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFilePath() , $format);
                if($rows === false){
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', array(), 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    unlink('.' . $entity->getFile()['fileName']);
                    return false;
                }
                unset($rows);
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
                return true;
            }
            elseif($format == 'txt' || $format == 'tab' || $format == 'csv'){
                $fileReader = new ReadFile();
                if(memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number){
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', array(), 'DatasetsBundle'));
                    return false;
                }
                $rows = $fileReader->getRows('./assets' . $entity->getFile()['fileName'] , $format);
            } elseif($format == 'xls' || $format == 'xlsx'){
                $objPHPExcel = PHPExcel_IOFactory::load('./assets' . $entity->getFile()['fileName']);
                $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                array_unshift($rows, null);
                unset($rows[0]);
            } else{
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return false;
            }
            $hasHeaders = false;
            if(!empty($rows)){
                foreach($rows[1] as $header){
                    if(!(is_numeric($header))){
                        $hasHeaders = true;
                    }
                }
            }
            $arff = '';
            $arff .= '@relation ' . $filename . PHP_EOL;
            if($hasHeaders){
                foreach($rows[1] as $key => $header){
                    // Remove spaces in header, to fit arff format
                    $header = preg_replace('/\s+/', '_', $header);

                    // Check string is numeric or normal string
                    if (is_numeric($rows[2][$key])) {
                        if(is_int($rows[2][$key] + 0))
                            $arff .= '@attribute ' . $header . ' ' . 'integer' . PHP_EOL;
                        else if(is_float($rows[2][$key] + 0))
                            $arff .= '@attribute ' . $header . ' ' . 'real' . PHP_EOL;
                    } else {
                        $arff .= '@attribute ' . $header . ' ' . 'string' . PHP_EOL;
                    }
                }
            } else {
                foreach($rows[1] as $key => $header){
                    if (is_numeric($rows[2][$key])) {
                        if(is_int($rows[2][$key] + 0))
                            $arff .= '@attribute ' . 'attr' . $key . ' ' . 'integer' . PHP_EOL;
                        else if(is_float($rows[2][$key] + 0))
                            $arff .= '@attribute ' . 'attr' . $key . ' ' . 'real' . PHP_EOL;
                    } else {
                        $arff .= '@attribute ' . 'attr' . $key . ' ' . 'string' . PHP_EOL;
                    }
                }
            }
            $arff .= '@data' . PHP_EOL;
            if($hasHeaders)
                unset($rows[1]);
            foreach($rows as $row){
                foreach($row as $key => $value)
                    if($key > 0)
                        $arff .= ',' . $value;
                    else
                        $arff .= $value;
                $arff .= PHP_EOL;
            }
            $dir = substr($entity->getFile()['path'], 0, strripos($entity->getFile()['path'], '.'));
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . $dir . ".arff","w+");
            fwrite($fp, $arff);
            fclose($fp);
            $entity->setFilePath($dir . ".arff");
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', array(), 'DatasetsBundle'));
            return true;
        }
        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error!', array(), 'DatasetsBundle'));
        return false;
    }
}
