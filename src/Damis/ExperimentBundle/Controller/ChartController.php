<?php

namespace Damis\ExperimentBundle\Controller;

use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Helpers\Chart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client; 
use CURLFile;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChartController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $params
    ) {
    }

    /**
     * Chart generation
     */
    #[Route("/experiment/chart/dataset.html", name: "dataset_chart", methods: ["GET", "POST"], options: ["expose" => true])]
    function getAction(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        if ($request->isMethod('POST')) {
            if ($request->get('dst') == 'user-computer') {
                return $this->_downloadImage($request->get('image'), $request->get('format'));
            } 
            elseif ($request->get('dst') == 'midas') {
                if ($request->get('format') == 'jpeg' || $request->get('format') == 'png') {
                    $temp_file = $this->_getImageFilePath($request->get('image'), $request->get('format'));
                } else {
                    return new Response('Invalid format', 400);
                }
                
                $client = new Client(['base_uri' => $this->params->get('midas_url')]);
                $session = $request->getSession(); 
                
                if ($session->has('sessionToken')) {
                    $sessionToken = $session->get('sessionToken');
                } else {
                    $session->getFlashBag()->add('error', $translator->trans('Error uploading file', [], 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));
                }
                
                $fileinfo = pathinfo($temp_file);
                $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileinfo['basename']);
                        
                $post = [
                    'name' => $name.'.'.$request->get('format'),
                    'parentFolderId' => json_decode((string) $request->get('path'), true)['idCSV'],
                    'size' => filesize($temp_file),
                ];

                try {
                    $req = $client->post('/action/file-explorer/file/init', [
                        'headers' => ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken],
                        'body' => json_encode($post)
                    ]);
                    $response = json_decode($req->getBody()->getContents(), true);

                    if (isset($response['type']) && $response['type'] == 'error') {
                        $session->getFlashBag()->add('error', $translator->trans($response["msgCode"], [], 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }

                    $fileId = $response['file']['id'];
                    $header = ['Content-Type: multipart/form-data', 'Authorization:'.$sessionToken];

                    $file = new CURLFile($temp_file, 'image/'.$request->get('format'), $name);
                    $fields = ['slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1];

                    $resource = curl_init();
                    curl_setopt($resource, CURLOPT_URL, $this->params->get('midas_url').'/action/file-explorer/file/slice');
                    
                    curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($resource, CURLOPT_POST, 1);
                    curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

                    $result = curl_exec($resource);
                    curl_close($resource);
                    
                    if (file_exists($temp_file)) unlink($temp_file);
                    
                    $session->getFlashBag()->add('success', $translator->trans('File uploaded successfully', [], 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));

                } catch (\Exception $e) {
                    $session->getFlashBag()->add('error', $translator->trans('Error uploading file', [], 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));
                }
            }
        } 
        else {
            $params = $request->query->all();
            
            $dataset = $doctrine->getRepository(Dataset::class)->findOneBy(['datasetId' => $params['dataset_url']]);

            if (!$dataset) {
                 return new JsonResponse(['status' => 'ERROR', 'message' => 'Dataset not found']);
            }

            $helper = new Chart();
            $fullPath = $this->params->get('kernel.project_dir') . '/public' . $dataset->getFilePath();
            
            $x = $params['x'] ?? null;
            $y = $params['y'] ?? null;
            $clsCol = $params['cls'] ?? null;
            
            $chart = $helper->classifieData($fullPath, $x, $y, $clsCol);

            // Check if there was an error (insufficient data)
            if (isset($chart['error'])) {
                $html = $this->renderView('@DamisExperiment/_chart.html.twig', [
                    'context' => [],
                    'error' => true,
                    'error_message' => $chart['error']
                ]);

                return new JsonResponse([
                    'status' => 'ERROR',
                    'html' => $html,
                    'message' => $chart['error'],
                    'content' => $chart['content']
                ]);
            }

            $context = [
                "attrs" => $chart['attributes'],
                "x" => $chart['x'],
                "y" => $chart['y'],
                "cls" => $chart['clsCol'],
                "float_cls" => (isset($chart['attributes'][$chart['clsCol']][0]['type']) && ($chart['attributes'][$chart['clsCol']][0]['type'] == 'real' || $chart['attributes'][$chart['clsCol']][0]['type'] == 'numeric')),
                "minCls" => $chart['content']["minCls"],
                "maxCls"=> $chart['content']["maxCls"],
            ];

            $html = $this->renderView('@DamisExperiment/_chart.html.twig', ['context' => $context, 'error' => false]);

            return new JsonResponse([
                'status' => 'SUCCESS',
                'html' => $html,
                'content' => $chart['content']
            ]);
        }
    }

    private function _downloadImage($image, $format)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
            $format = strtolower($type[1]); 
        }

        $image = str_replace(' ', '+', $image);
        $data = base64_decode($image);
        
        if ($data === false) {
            return new Response("Error decoding image data", 500);
        }

        $response = new Response($data);
        $response->headers->set('Content-Type', 'image/' . $format);
        $response->headers->set('Content-Disposition', 'attachment; filename="chart.'.$format.'"');

        return $response;
    }
    
    /**
     * Create temp image file file
     */
    private function _getImageFilePath($image, $format)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
            $image = substr($image, strpos($image, ',') + 1);
        }

        $image = str_replace(' ', '+', $image);
        $id = time();

        $data = base64_decode($image);
        
        $cacheDir = $this->params->get('kernel.cache_dir');
        $filePath = $cacheDir . '/chart' . $id . '.' . $format;
        
        file_put_contents($filePath, $data);

        return $filePath;
    }
}