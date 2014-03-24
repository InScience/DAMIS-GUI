<?php

namespace Damis\ExperimentBundle\Controller;

use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\DamisExperimentBundle;
use Damis\ExperimentBundle\Helpers\Chart;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;
use Symfony\Component\HttpFoundation\Response;

class ChartController extends Controller
{
    /**
     * Chart generation
     *
     * @Route("/experiment/chart/dataset.html", name="dataset_chart", options={"expose" = true})
     * @Method({"GET", "POST"})
     */
    function getAction(Request $request) {
        if($request->isMethod('POST')) {
            return $this->_downloadImage($request->get('image'), $request->get('format'));
        } else {
            $params = $request->query->all();
            /** @var $dataset Dataset */
            $dataset = $this->getDoctrine()
                ->getRepository('DamisDatasetsBundle:Dataset')
                ->findOneByDatasetId($params['dataset_url']);

            $helper = new Chart();
            $x = isset($params['x']) ? $params['x'] : null;
            $y = isset($params['y']) ? $params['y'] : null;
            $clsCol = isset($params['cls']) ? $params['cls'] : null;
            $chart = $helper->classifieData(realpath($this->get('kernel')->getRootDir()
                . '/../web' . $dataset->getFilePath()), $x, $y, $clsCol);

            $context = [
                "attrs" => $chart['attributes'],
                "x" => $chart['x'],
                "y" => $chart['y'],
                "cls" => $chart['clsCol'],
                "float_cls" => $chart['attributes'][$chart['clsCol']][0]['type'] == 'real',
                "minCls" => $chart['content']["minCls"],
                "maxCls"=> $chart['content']["maxCls"],
            ];

            $html = $this->render(
                'DamisExperimentBundle::_chart.html.twig',
                ['context' => $context, 'error' => false]
            );

            return new JsonResponse(
                [
                    'status' => 'SUCCESS',
                    'html' => $html->getContent(),
                    'content' => $chart['content']
                ]
            );
        }
    }

    private function _downloadImage($image, $format)
    {
//        echo $image;
//        echo'<img src="'.$image.'" />' ;

       // $image = str_replace('data:image/octet;base64,', '', $image);
$_image = $image;
        $image = str_replace(' ', '+', $image);

        $data = base64_decode($image);

        $imageInfo = $image; // Your method to get the data
        $image = fopen($imageInfo, 'wb');
        file_put_contents(realpath($this->get('kernel')->getRootDir())
                . '/cache/chart.' . $format, $image);
        fclose($image);
        $response = new Response();
        $response->headers->set('Content-Type', $format);
        $response->headers->set('Content-Disposition', 'attachment; filename="chart.' . $format . '"');

        $response->setContent(file_get_contents(realpath($this->get('kernel')->getRootDir())
                . '/cache/chart.' . $format));
        return $response;
    }
}
