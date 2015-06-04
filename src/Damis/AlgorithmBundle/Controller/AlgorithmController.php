<?php

namespace Damis\AlgorithmBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Damis\AlgorithmBundle\Entity\File;
use Damis\AlgorithmBundle\Form\Type\FileType;

/**
 * Algorithms controller.
 *
 * @Route("/algorithm")
 */
class AlgorithmController extends Controller
{
    /**
     * User algorithms list window
     * 
     * @Route("/list.html", name="algorithm_list")
     * @Template()
     */
    public function listAction(Request $request)
    {
        $sort = $request->get('order_by');
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        if($sort == 'titleASC')
            $entities = $em->getRepository('DamisAlgorithmBundle:File')
                ->getUserAlgorithms($user, array('title' => 'ASC'));
        elseif($sort == 'titleDESC')
            $entities = $em->getRepository('DamisAlgorithmBundle:File')
                ->getUserAlgorithms($user, array('title' => 'DESC'));
        elseif($sort == 'createdASC')
            $entities = $em->getRepository('DamisAlgorithmBundle:File')
                ->getUserAlgorithms($user, array('created' => 'ASC'));
        elseif($sort == 'createdDESC')
            $entities = $em->getRepository('DamisAlgorithmBundle:File')
                ->getUserAlgorithms($user, array('created' => 'DESC'));
        else
            $entities = $em->getRepository('DamisAlgorithmBundle:File')->getUserAlgorithms($user);
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $entities, $this->get('request')->query->get('page', 1), 15);
        return array(
            'entities' => $pagination
        );
    }
    
    /**
     * Upload new algorithm
     *
     * @Route("/new.html", name="algorithm_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new File();
        $form = $this->createForm(new FileType(), $entity);
        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * Create new algorithm
     *
     * @Route("/create.html", name="algorithm_create")
     * @Method("POST")
     * @Template("DamisAlgorithmBundle:Algorithm:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new File();
        $form = $this->createForm(new FileType(), $entity);
        $form->submit($request);
        $user = $this->get('security.context')->getToken()->getUser();
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity->setFileCreated(time());
            $entity->setUser($user);
            $em->persist($entity);
            $em->flush();
            // Update file path parameter
            if (isset($entity->getFile()["path"])) {
                $entity->setFilePath($entity->getFile()["path"]);
                $em->persist($entity);
                $em->flush();
            }
            $this->get('session')->getFlashBag()->add('success', $this->container->get('translator')->trans('Algorithm successfully uploaded! Project administrators will connect with you for next actions', array(), 'AlgorithmBundle'));
            return $this->redirect($this->generateUrl('algorithm_new'));
        }

        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * Edit algorithm
     *
     * @Route("/{id}/edit.html", name="algorithm_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $entity \Damis\AlgorithmBundle\Entity\File */
        $entity = $em->getRepository('DamisAlgorithmBundle:File')->findOneByFileId($id);
        // Validation of user access to current experiment
        if (!$entity || ($entity->getUser() != $user) ) { 
            $this->container->get('logger')->addError('Unvalid try to access dataset by user id: ' . $user->getId());
            return $this->redirectToRoute('algorithm_list');
        }        
        $form = $this->createForm(new FileType(), null);
        $form->get('fileTitle')->setData($entity->getFileTitle());
        $form->get('fileDescription')->setData($entity->getFileDescription());
        return array(
            'form' => $form->createView(),
            'id' => $entity->getFileId()
        );
    }

    /**
     * Update algorithm file description
     *
     * @Route("/{id}/update.html", name="algorithm_update")
     * @Method("POST")
     * @Template("DamisAlgorithmBundle:Algorithm:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        /* @var $entity \Damis\AlgorithmBundle\Entity\File */
        $entity = $em->getRepository('DamisAlgorithmBundle:File')->findOneByFileId($id);
        // Validation of user access to current experiment
        if (!$entity || ($entity->getUser() != $user) ) { 
            $this->container->get('logger')->addError('Unvalid try to access dataset by user id: ' . $user->getId());
            return $this->redirectToRoute('algorithm_list');
        }
        $form = $this->createForm(new FileType(), null);
        $form->offsetUnset('file');
        $form->get('fileTitle')->setData($entity->getFileTitle());
        $form->get('fileDescription')->setData($entity->getFileDescription());
        $form->submit($request);
        if ($form->get('fileTitle')->isValid() && $form->get('fileDescription')->isValid()) {
            $data = $request->get('file_newtype');
            $entity->setFileUpdated(time());
            $entity->setFileTitle($data['fileTitle']);
            $entity->setFileDescription($data['fileDescription']);
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Algorithm file successfully updated!');
            return $this->redirect($this->generateUrl('algorithm_list'));
        }
        return array(
            'form' => $form->createView(),
            'id' => $id
        );
    }
    
    /**
     * Delete algorithms
     *
     * @Route("/delete.html", name="algorithm_delete")
     * @Method("POST")
     * @Template()
     */
    public function deleteAction(Request $request)
    {
        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->get('security.context')->getToken()->getUser();
        
        $files = json_decode($request->request->get('file-delete-list'));
        $em = $this->getDoctrine()->getManager();
        foreach ($files as $id){
            /* @var $file \Damis\AlgorithmBundle\Entity\File */
            $file = $em->getRepository('DamisAlgorithmBundle:File')->findOneByFileId($id);
            if ($file && ($file->getUser() == $user)){
                if (file_exists('.' . $file->getFilePath()))
                    if ($file->getFilePath())
                        unlink('.' . $file->getFilePath());
                $em->remove($file);
                $em->flush();
            }
        }
        return $this->redirect($this->generateUrl('algorithm_list'));
    }
}
