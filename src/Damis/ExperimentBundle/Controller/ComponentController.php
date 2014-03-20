<?php

namespace Damis\ExperimentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;

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
     * Component info
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

        $formType = 'Damis\ExperimentBundle\Form\Type\\' . $component->getFormType() . 'Type';
        $form = $this->createForm(new $formType());

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

                return $this->render(
                    'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
                    [
                        'form' => $form->createView(),
                        'response' => json_encode($response),
                    ]
                );
            }
        }
        return $this->render(
            'DamisExperimentBundle:Component:' . strtolower($component->getFormType()) . '.html.twig',
            ['form' => $form->createView()]
        );
    }

}
