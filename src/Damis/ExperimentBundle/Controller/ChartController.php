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
            if($request->get('dst') == 'user-computer')
                return $this->_downloadImage($request->get('image'), $request->get('format'));
            // Midas
            else if ($request->get('dst') == 'midas') {
                if($request->get('format') == 'jpeg' || $request->get('format') == 'png') {
                    $temp_file = $this->_getImageFilePath($request->get('image'), $request->get('format'));
                } else {
                    // Error 
                   return 0;
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
                $fileinfo = pathinfo($temp_file);
                $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileinfo['basename']);
                        
                $post = array(
                    'name' => $name,
                    'path' => json_decode($request->get('path'), true)['path'],
                    'repositoryType' => 'research',
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

                    $file = new CURLFile($temp_file, 'image/'. $request->get('format'), $name);

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
            $params = $request->query->all();
            /** @var $dataset Dataset */
            $dataset = $this->getDoctrine()
                ->getRepository('DamisDatasetsBundle:Dataset')
                ->findOneByDatasetId($params['dataset_url']);

            $helper = new Chart();
            $x = isset($params['x']) ? $params['x'] : null;
            $y = isset($params['y']) ? $params['y'] : null;
            $clsCol = isset($params['cls']) ? $params['cls'] : null;
            $chart = $helper->classifieData('.' . $dataset->getFilePath(), $x, $y, $clsCol);

            $context = [
                "attrs" => $chart['attributes'],
                "x" => $chart['x'],
                "y" => $chart['y'],
                "cls" => $chart['clsCol'],
                "float_cls" => ($chart['attributes'][$chart['clsCol']][0]['type'] == 'real' || $chart['attributes'][$chart['clsCol']][0]['type'] == 'numeric'),
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
    
    /**
     * Create temp image file file
     * 
     * @param type $image
     * @param type $format
     * @return type
     */
    private function _getImageFilePath($image, $format)
    {
        $image = str_replace(' ', '+', $image);

        $data = base64_decode($image);

        $imageInfo = $image; // Your method to get the data
        $image = fopen($imageInfo, 'wb');
        file_put_contents(realpath($this->get('kernel')->getRootDir())
                . '/cache/chart.' . $format, $image);
        fclose($image);

        return realpath($this->get('kernel')->getRootDir())
                . '/cache/chart.' . $format;
    }
}
