<?php

namespace Damis\DatasetsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DatasetsController extends Controller
{
    /**
     * Users datasets list window
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
