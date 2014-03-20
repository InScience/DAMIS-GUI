<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;

class ChartController extends Controller
{
    /**
     * Chart generation
     *
     * @Route("/experiment/chart/dataset/{id}.html", name="dataset_charset", options={"expose" = true})
     * @Method({"GET", "POST"})
     */
    function getAction(Request $request) {
        if($request->isPost()) {
            // download image
        } else {
            // chart render
        }
    }
}
