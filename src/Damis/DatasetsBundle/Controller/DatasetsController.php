<?php

namespace Damis\DatasetsBundle\Controller;

use Damis\DatasetsBundle\Form\Type\DatasetType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class DatasetsController extends Controller
{
    /**
     * User datasets list window
     *
     * @Route("/datasets/list.html", name="datasets_list")
     * @Template()
     */
    public function listAction()
    {
        return array(

        );
    }

    /**
     * Upload new dataset
     *
     * @Route("/datasets/new.html", name="datasets_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $form = $this->createForm(new DatasetType());
        return array(
            'form' => $form->createView()
        );
    }
    /**
     * Create new dataset
     *
     * @Route("/datasets/new.html", name="datasets_create")
     * @Method("POST")
     * @Template()
     */
    public function createAction()
    {
        return array(

        );
    }

    /**
     * Dataset upload component form
     *
     * @Route("/datasets/upload.html", name="dataset_upload")
     * @Template()
     */
    public function uploadAction()
    {
        return [];
    }
}
