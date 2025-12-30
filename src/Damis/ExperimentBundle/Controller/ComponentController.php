<?php

namespace Damis\ExperimentBundle\Controller;

use GuzzleHttp\Exception\ClientException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Damis\EntitiesBundle\Entity\Workflowtask;
use Base\ConvertBundle\Helpers\ReadFile;
use CURLFile;
use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Entity\Component;
use Damis\ExperimentBundle\Entity\Parameter;
use Damis\ExperimentBundle\Helpers\Experiment as ExperimentHelper;
use GuzzleHttp\Client;
use PHPExcel_IOFactory;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Damis\ExperimentBundle\Entity\Experiment as Experiment;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Damis\ExperimentBundle\Form\Type\FilterType;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Iphp\FileStoreBundle\Mapping\Factory as IphpMappingFactory;
use Iphp\FileStoreBundle\FileStorage\FileStorageInterface;
use Psr\Log\LoggerInterface;


class ComponentController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaginatorInterface $paginator,
        private readonly ParameterBagInterface $params,
        private readonly ?IphpMappingFactory $mappingFactory = null,
        private readonly ?FileStorageInterface $fileStorage = null,
        private readonly ExperimentHelper $experimentHelper,
        private readonly ?LoggerInterface $logger = null
    )
    {

    }

    /**
     * Component info
     */
    #[Route('/experiment/component/{id}/info.html', name: 'component', methods: ['GET'], options: ['expose' => true])]
    public function component($id): Response
    {
        $em = $this->doctrine->getManager();
        $component = $em->getRepository(Component::class)->find($id);
        if (!$component) {
            throw $this->createNotFoundException("Component with ID {$id} not found.");
        }
        $parameters = $component->getParameters();

        return $this->render('@DamisExperiment/component/component.html.twig', [
            'component' => $component,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Component form
     */
    #[Route("/experiment/component/{id}/form.html", name: "component_form", methods: ["GET", "POST"], options: ["expose" => true])]
    public function componentForm(Request $request, $id): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $em = $this->doctrine->getManager();

        /** @var Component|null $component */
        $component = $em->getRepository(Component::class)->find($id);

        if (!$component) {
            throw $this->createNotFoundException('The component does not exist');
        }

        $options = ['choices' => [], 'class' => []];
        $datasetId = $request->query->get('dataset_id') ?? $request->request->get('dataset_id');
        if ($datasetId > 0) {
            /** @var \Damis\DatasetsBundle\Entity\Dataset|null $dataset */
            $dataset = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $datasetId]);
            if ($dataset) {
                $helper = new \Base\ConvertBundle\Helpers\ReadFile();
                $filePath = $this->getParameter('kernel.project_dir') . '/public' . $dataset->getFilePath();
                if (file_exists($filePath)) {
                    $attributes = $helper->getAttributes($filePath);
                    $class = $helper->getClassAttr($filePath);
                    $options['choices'] = array_flip($attributes);
                    
                    // Convert indexed array to associative array [name => index]
                    // This is required for Symfony ChoiceType which expects [label => value]
                    $classAssoc = [];
                    foreach ($class as $index => $name) {
                        $classAssoc[$name] = $index;
                    }
                    $options['class'] = $classAssoc;
                }
            }
        }

        $formType = 'Damis\ExperimentBundle\Form\Type\\' . $component->getFormType() . 'Type';
        $form = $this->createForm($formType, null, $options);
        $data = json_decode((string) $request->query->get('data'));
        $formData = [];
        if ($request->isMethod('GET') && !empty($data)) {
            $parametersIds = [];
            $values = [];
            foreach ($data as $parameter) {
                $parametersIds[$parameter->id] = $parameter->id;
                $values[$parameter->id] = $parameter->value;
            }

            $parameters = $this->experimentHelper->getParameters($parametersIds);

            foreach ($parameters as $param) {
                if ($form->has($param->getSlug())) {
                    $form->get($param->getSlug())->setData($values[$param->getId()]);
                    $formData[$param->getSlug()] = $values[$param->getId()];
                }
            }
        }

        if ($request->isMethod('POST')) {
            error_log('=== ComponentController POST Request ===');
            error_log('Component ID: ' . $id);
            error_log('Component Type: ' . $component->getFormType());
            error_log('Request data: ' . json_encode($request->request->all()));
            error_log('Options class: ' . json_encode($options['class']));
            error_log('Options choices: ' . json_encode($options['choices']));
            
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                error_log('Form submitted: true');
                error_log('Form valid: ' . ($form->isValid() ? 'true' : 'false'));
                
                if (!$form->isValid()) {
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errorData = [
                            'field' => $error->getOrigin()?->getName(),
                            'message' => $error->getMessage()
                        ];
                        
                        $cause = $error->getCause();
                        if ($cause && method_exists($cause, 'getInvalidValue')) {
                            $errorData['invalid_value'] = $cause->getInvalidValue();
                        } elseif ($cause) {
                            $errorData['cause_class'] = get_class($cause);
                            $errorData['cause_message'] = method_exists($cause, 'getMessage') ? $cause->getMessage() : 'N/A';
                        }
                        
                        $errors[] = $errorData;
                    }
                    
                    error_log('Form errors: ' . json_encode($errors, JSON_PRETTY_PRINT));
                    
                    $this->logger?->error('Component form validation failed', [
                        'component_id' => $id,
                        'query_params' => $request->query->all(),
                        'dataset_id' => $datasetId,
                        'errors' => (string) $form->getErrors(true, false),
                        'submitted_data' => $request->request->all(),
                        'generated_choices' => $options['choices']
                    ]);
                }
            }

            if ($form->isSubmitted() && $form->isValid()) {
                $parameters = $em->getRepository(Parameter::class)->findBy(['component' => $id]);
                $response = [];
                $formParam = strtolower((string) $component->getFormType()) . '_type';
                $requestParams = $request->request->all($formParam);

                foreach ($parameters as $parameter) {
                    if ($parameter->getConnectionType()->getId() == 3) {
                        if (isset($requestParams[$parameter->getSlug()])) {
                            $response[$parameter->getId()] = $requestParams[$parameter->getSlug()];
                        }
                    }
                }

                $html = $this->renderView(
                    '@DamisExperiment/component/' . strtolower((string) $component->getFormType()) . '.html.twig',
                    [
                        'form' => $form->createView(),
                        'response' => json_encode($response),
                        'form_data' => $requestParams,
                        'dataset_id' => $datasetId
                    ]
                );
                return new \Symfony\Component\HttpFoundation\JsonResponse(["html" => $html, 'componentId' => $id]);
            }
        }

        $response = $formData;
        if ($request->isMethod('GET') && empty($data)) {
            $parameters = $em->getRepository(Parameter::class)->findBy(['component' => $id]);
            foreach ($parameters as $parameter) {
                if ($parameter->getSlug() && $parameter->getConnectionType()->getId() == 3 && $form->has($parameter->getSlug())) {
                    $response[$parameter->getId()] = $form->get($parameter->getSlug())->getData();
                }
            }
        }

        $html = $this->renderView(
            '@DamisExperiment/component/' . strtolower((string) $component->getFormType()) . '.html.twig',
            [
                'form' => $form->createView(),
                'response' => json_encode($response),
                'form_data' => $formData,
                'dataset_id' => $datasetId
            ]
        );

        return new \Symfony\Component\HttpFoundation\JsonResponse(["html" => $html, 'componentId' => $id]);
    }

    /**
     * User datasets list window
     */
    #[Route("/experiment/component/existingFile.html", name: "existing_file", methods: ["GET", "POST"], options: ["expose" => true])]
    public function existingFile(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $sort = $request->query->get('order_by');
        $id = $request->query->get('id');
        $entity = null;

        $dataJson = $request->query->get('data');
        if ($dataJson) {
            $data = json_decode($dataJson);
            if (!empty($data) && isset($data[0]->value)) {
                $id = $data[0]->value;
            }
        }

        if ($id && $id !== 'undefined') {
            $entity = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $id]);
        } else {
            $id = null;
        }

        $user = $this->getUser();

        $order = ['created' => 'DESC'];
        if ($sort === 'titleASC') $order = ['title' => 'ASC'];
        elseif ($sort === 'titleDESC') $order = ['title' => 'DESC'];
        elseif ($sort === 'createdASC') $order = ['created' => 'ASC'];
        elseif ($sort === 'createdDESC') $order = ['created' => 'DESC'];

        $entities = $em->getRepository(Dataset::class)->getUserDatasets($user, $order);
        $pagination = $this->paginator->paginate(
            $entities,
            $request->query->getInt('page', 1),
            8
        );

        return $this->render('@DamisExperiment/component/existingFile.html.twig', [
            'entities' => $pagination,
            'selected' => $id,
            'file' => $entity
        ]);
    }

    /**
     * User midas datasets list window
     */
    #[Route("/experiment/component/existingMidasFile.html", name: "existing_midas_file", methods: ["GET", "POST"], options: ["expose" => true])]
    public function existingMidasFile(Request $request, TranslatorInterface $translator): Response
    {
        $client = new \GuzzleHttp\Client(['base_uri' => $this->params->get('midas_url')]);
        $em = $this->doctrine->getManager();

        $session = $request->getSession();
        $sessionToken = $session->get('sessionToken');
        $this->logger?->info('existingMidasFile request', [
            'query' => $request->query->all(),
            'data' => $request->get('data'),
            'sessionToken_present' => (bool) $sessionToken,
        ]);

        if (!$sessionToken) {
            return new Response(
                $translator->trans('It is impossible access MIDAS. Please re-login to MIDAS', [], 'ExperimentBundle'),
                Response::HTTP_FORBIDDEN
            );
        }

        if ($request->isMethod("POST")) {
            $data = json_decode((string) json_decode((string) $request->get('data'), true)[0]['value'], true);
            try {
                $response = $client->get('/action/file-explorer/file', [
                    'query' => [
                        'path' => $data['path'],
                        'name' => $data['name'],
                        'idCSV' => $data['idCSV'],
                        'authorization' => $sessionToken
                    ]
                ]);
                $body = $response->getBody()->getContents();

                $file = new Dataset();
                $file->setDatasetTitle(basename((string) $data['name']));
                $file->setDatasetCreated(new \DateTime());
                $file->setUser($this->getUser());
                $file->setDatasetIsMidas(true);
                $temp_file = $this->params->get("kernel.cache_dir") . '/' . uniqid() . $data['name'];
                $em->persist($file);
                $em->flush();
                file_put_contents($temp_file, $body);

                $file2 = new File($temp_file);

                if ($this->mappingFactory && $this->fileStorage) {
                    $ref_class = new \ReflectionClass(Dataset::class);
                    $mapping = $this->mappingFactory->getMappingFromField($file, $ref_class, 'file');
                    $file_data = $this->fileStorage->upload($mapping, $file2);
                } else {
                    $this->addFlash('error', 'File storage services are not configured.');
                    return $this->redirectToRoute('existing_midas_file'); // Or some other appropriate route
                }

                $org_file_name = basename((string) $data['name']);
                $file_data['originalName'] = $org_file_name;

                $file->setFile($file_data);
                // No need to persist again, flush is enough for an already managed entity
                $em->flush();
                unlink($temp_file);
                // $this->uploadArff($file->getDatasetId()); // This call needs to be verified

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $this->addFlash('error', $translator->trans('Error fetching file', [], 'DatasetsBundle'));
                return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Error fetching file: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->render('@DamisExperiment/component/existingMidasFile.html.twig', [
                'file' => $file,
                'files' => null
            ]);
        }

        if (isset($request->query->all()['dataset_url'])) {
            $data = json_decode((string) $request->query->all()['dataset_url']);
            if ($request->query->all() && !empty($data)) {
                $datasetId = $data[0]->value;
                $dataset = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $datasetId]);
                return $this->render('@DamisExperiment/component/existingMidasFile.html.twig', [
                    'file' => $dataset,
                    'files' => null
                ]);
            }
        }
        $page = $request->query->getInt('page', 1);
        $path = $request->query->get('path', '');
        $uuid = $request->query->get('uuid', 'research');
        $id = $request->query->get('id');

        $data = json_decode((string) $request->get('data'));
        if ($request->get('data') && !empty($data) && $request->get('edit') != 1) {
            $id = json_decode((string) $request->get('data'))[0]->value;
            $dataset = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $id]);
            return $this->render('@DamisExperiment/component/existingMidasFile.html.twig', [
                'file' => $dataset,
                'files' => null
            ]);
        }
        // Default path
        if (!$path) {
            $files = [
                'details' => [
                    'folderDetailsList' => [
                        ['name' =>  $translator->trans('Published research', [], 'DatasetsBundle'), 'path' => 'publishedResearch', 'type' => 'RESEARCH', 'modifyDate' => time() * 1000, 'page' => 0, 'uuid' => 'publishedResearch', 'resourceId'   => ''],
                        ['name' => $translator->trans('Not published research', [], 'DatasetsBundle'), 'path' => 'research', 'type' => 'RESEARCH', 'modifyDate' => time() * 1000, 'page' => 0, 'uuid' => 'research', 'resourceId'   => '']
                    ]
                ]
            ];
            return $this->render('@DamisExperiment/component/existingMidasFile.html.twig', [
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
            ]);
        }
        // Else if $path is selected
        $post = ['page' => $page, 'pageSize' => 10, 'uuid' => $uuid];
        $files = [];
        try {
            $response = $client->post('/action/research/folders', [
                'headers' => ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken],
                'json' => $post
            ]);
            if ($response->getStatusCode() == 200) {
                $files = json_decode($response->getBody()->getContents(), true);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return new Response(
                $translator->trans('It is impossible access MIDAS. Please re-login to MIDAS', [], 'ExperimentBundle') . ' ' . $e->getMessage(),
                Response::HTTP_FORBIDDEN
            );
        }

        $pageCount = $files['details']['pageCount'] ?? 0;
        // Remove bad files
        $extensions = ['txt', 'tab', 'csv', 'xls', 'xlsx', 'arff', 'zip'];
        if (isset($files['details']['folderDetailsList'])) {
            $tmpItems = $files['details']['folderDetailsList'];
            foreach ($tmpItems as $nr => $item) {
                if ($item['type'] == 'FILE' && !in_array(pathinfo((string) $item['name'], PATHINFO_EXTENSION), $extensions)) {
                    unset($files['details']['folderDetailsList'][$nr]);
                }
            }
        }
        return $this->render('@DamisExperiment/component/existingMidasFile.html.twig', [
            'file' => null,
            'files' => $files,
            'page' => $page,
            'pageCount' => $pageCount,
            'previous' => $page - 1,
            'next' => $page + 1,
            'path' => $path,
            'uuid' => $uuid,
            'selected' => $id
        ]);
    }


    /**
     * User midas datasets list
     */
    #[Route("/experiment/component/existingMidasFolders.html", name: "existing_midas_folders", methods: ["GET", "POST"], options: ["expose" => true])]
    #[Template('@DamisExperiment/component/midasFolders.html.twig')]
    public function midasFolders(Request $request)
    {
        $client = new \Guzzle\Http\Client($this->container->getParameter('midas_url')); // Guzzle 3

        $session = $request->getSession();
        $sessionToken = '';
        if ($session->has('sessionToken')) {
            $sessionToken = $session->get('sessionToken');
        } else {
            return new Response($this->get('translator')->trans('It is impossible access MIDAS. Please re-login to MIDAS', [], 'ExperimentBundle'));
        }
        $page = $request->get('page') ?: 1;
        $path = $request->get('path') ?: '';
        $uuid = $request->get('uuid') ?: 'research';
        $id = $request->get('id');

        $data = json_decode((string) $request->get('data'));
        if ($request->get('data') && !empty($data) && $request->get('edit') != 1) {
            $id = json_decode((string) $request->get('data'))[0]->value;
            $path = json_decode($id, true)['path'];
            $page = json_decode($id, true)['page'];

            $folders = explode('/', (string) $path);
            $count = count($folders);
            $path = '';
            foreach ($folders as $key => $p) {
                if ($key < $count - 1) {
                    $path .= $p . '/';
                }
            }
        }
        // Default path
        if (!$path) {
            $files = ['details' =>
                ['folderDetailsList' =>
                    [0 =>
                        ['name' =>  $this->get('translator')->trans('Published research', [], 'DatasetsBundle'), 'path' => 'publishedResearch', 'type' => 'RESEARCH', 'modifyDate' => time() * 1000, 'page' => 0, 'uuid' => 'publishedResearch', 'resourceId'   => ''], 1 =>
                        ['name' => $this->get('translator')->trans('Not published research', [], 'DatasetsBundle'), 'path' => 'research', 'type' => 'RESEARCH', 'modifyDate' => time() * 1000, 'page' => 0, 'uuid' => 'research', 'resourceId'   => '']]]];
            return ['files' => $files, 'page' => 0, 'pageCount' => 1, 'totalFiles' => 0, 'previous' => 0, 'next' => 0, 'path' => $path, 'uuid' => '', 'selected' => 0];
        }
        /// Else if $path is selected
        $post = [
            'page' => $page,
            'pageSize' => 10,
            'uuid' => $uuid,
        ];
        $files = [];

        $req = $client->post(
            '/action/research/folders',
            ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken],
            json_encode($post) // Guzzle 3 needs body as 3rd param
        );
        try {
            $response = $req->send();
            if ($response->getStatusCode() == 200) {
                $files = json_decode((string) $response->getBody(true), true);
            }
        } catch (\Guzzle\Http\Exception\BadResponseException $e) { // Guzzle 3 exception
            $req = $client->post('/action/authentication/session/' . $sessionToken . '/check', ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken]);
            try {
                $req->send()->getBody(true);
            } catch (\Guzzle\Http\Exception\BadResponseException $e2) {
                return new Response($this->get('translator')->trans('It is impossible access MIDAS. Please re-login to MIDAS', [], 'ExperimentBundle'));
            }
        }
        if (isset($files['details'])) {
            $pageCount = $files['details']['pageCount'];
        } else {
            $pageCount = 0;
        }
        return ['files' => $files, 'page' => $page, 'pageCount' => $pageCount, 'previous' => $page - 1, 'next' => $page + 1, 'path' => $path, 'uuid' => $uuid, 'selected' => $id];
    }

    /**
     * Matrix view
     */
    #[Route("/experiment/component/{id}/matrixView.html", name: "matrix_view", methods: ["GET", "POST"], options: ["expose" => true])]
    public function matrixView(Request $request, $id)
    {
        $em = $this->doctrine->getManager();

        $entity = null;
        $attributes = [];
        $rows = [];

        if ($id == 'undefined') {
            $id = null;
        } else {
            $entity = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $id]);

            if ($request->isMethod('POST')) {
                if ($request->get('dst') == 'user-computer') {
                    return $this->redirectToRoute('convert_' . $request->get('format'), ['id' => $id]);
                } elseif ($request->get('dst') == 'midas') {
                    $response2 = $this->forward('BaseConvertBundle:Convert:ConvertTo' . ucfirst((string) $request->get('format')), ['id'  => $id]);

                    if ($request->get('format') == 'xls' || $request->get('format') == 'xlsx') {
                        $temp_file = $response2->getContent();

                    } else {
                        $temp_file = $this->params->get("kernel.cache_dir") . '/../' . time() . $id;
                        $fp = fopen($temp_file, "w");
                        fwrite($fp, $response2->getContent());
                        fclose($fp);
                    }

                    $client = new Client(['base_uri' => $this->params->get('midas_url')]);
                    $session = $request->getSession();

                    if ($session->has('sessionToken')) {
                        $sessionToken = $session->get('sessionToken');
                    } else {
                        $this->addFlash('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }

                    $post = [
                        'name' =>  preg_replace('/\\.[^.\\s]{3,4}$/', '', (string) $entity->getFile()['originalName']) . $id . '.' . $request->get('format'),
                        'parentFolderId' => json_decode((string) $request->get('path'), true)['idCSV'],
                        'size' => $entity->getFile()['size'],
                    ];

                    try {
                        $req = $client->post('/action/file-explorer/file/init', [
                            'headers' => ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken],
                            'json' => $post
                        ]);
                        $response = json_decode($req->getBody()->getContents(), true);

                        if ($response['type'] == 'error') {
                            if ($response["msgCode"] == 'FILE_ResearchSpaceIsFull') {
                                $this->midasService->saveInTempDir($temp_file, $response2->headers->get('content-type'), preg_replace('/\\.[^.\\s]{3,4}$/', '', (string) $entity->getFile()['originalName']) . $id . '.' . $request->get('format'));
                            } else {
                                $this->addFlash('error', $this->translator->trans('MIDAS response', [], 'DatasetsBundle') . ': ' . $this->translator->trans($response["msgCodeTranslation"], [], 'DatasetsBundle'));
                            }
                            return $this->redirect($request->headers->get('referer'));
                        }

                        $fileId = $response['file']['id'];
                        $header = ['Content-Type' => 'multipart/form-data', 'Authorization' => $sessionToken];

                        $file = new CURLFile($temp_file, $response2->headers->get('content-type'), preg_replace('/\\.[^.\\s]{3,4}$/', '', (string) $entity->getFile()['originalName']) . $id . '.' . $request->get('format'));

                        $fields = ['slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1];

                        $resource = curl_init();
                        curl_setopt($resource, CURLOPT_URL, $this->params->get('midas_url') . '/action/file-explorer/file/slice');

                        $curlHeaders = [];
                        foreach($header as $k => $v) { $curlHeaders[] = is_numeric($k) ? $v : "$k: $v"; }
                        curl_setopt($resource, CURLOPT_HTTPHEADER, $curlHeaders);

                        curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($resource, CURLOPT_POST, 1);
                        curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

                        $result = curl_exec($resource);

                        curl_close($resource);

                        if (file_exists($temp_file)) {
                            unlink($temp_file);
                        }

                        $this->addFlash('success', $this->translator->trans('File uploaded successfully', [], 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));

                    } catch (ClientException $e) {
                        $this->addFlash('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
                        return $this->redirect($request->headers->get('referer'));
                    }
                }
            } else {
                if ($entity) {
                    $helper = new ReadFile();
                    $fullPath = $this->params->get('kernel.project_dir') . '/public' . $entity->getFilePath();

                    if(file_exists($fullPath)) {
                        $rows = $helper->getRows($fullPath, 'arff');
                        if($rows) {
                            foreach ($rows as $key => $row) {
                                if (mb_strtolower((string) $row[0]) != '@data') {
                                    if (str_starts_with(mb_strtolower((string) $row[key($row)]), '@attribute')) {
                                        $str = preg_replace('/\s+/i', " ", (string) $row[key($row)]);
                                        $attr = explode(' ', (string) $str);
                                        if (isset($attr[1]) && trim(strtoupper($attr[1])) != 'CLASS') {
                                            $attributes[] =  ['type' => $attr[2] ?? 'string', 'name' => $attr[1]];
                                        } else {
                                            if (isset($attr[2]) && $attr[2] == 'string') {
                                                $attributes[] =  ['type' => $attr[2], 'name' => $attr[1]];
                                            } else {
                                                $_row = implode(', ', $row);
                                                $_attr = explode('{', $_row);
                                                if (isset($_attr[1])) {
                                                    $__attr = explode('}', $_attr[1]);
                                                    $attributes[] = ['type' => $__attr[0], 'name' => 'CLASS'];
                                                }
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
                }
            }
        }

        return $this->render('@DamisExperiment/component/matrixView.html.twig', [
            'id' => $id,
            'attributes' => $attributes,
            'rows' => array_slice($rows, 0, 1000)
        ]);
    }

    /**
     * Technical information action
     */
    #[Route("/experiment/component/{id}/technical/information.html", name: "technical_information", methods: ["GET", "POST"], options: ["expose" => true])]
    public function technicalInformation(Request $request, $id): Response
    {
        $em = $this->doctrine->getManager();
        $entity = null;
        $message = '';
        $runtime = '';

        if ($id === 'undefined' || $id === null) {
        } else {
            $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);
        }

        if (!$entity) {
            throw $this->createNotFoundException('The dataset with id ' . $id . ' does not exist.');
        }

        if ($request->isMethod('POST')) {
            if ($request->get('dst') == 'user-computer') {
                return $this->redirectToRoute('convert_' . $request->get('format'), ['id' => $id]);
            } elseif ($request->get('dst') == 'midas') {
                /** @var Response $response2 */
                $response2 = $this->forward('BaseConvertBundle:Convert:ConvertTo' . ucfirst((string) $request->get('format')), ['id'  => $id, 'midas' => 1]);

                $temp_file = tempnam(sys_get_temp_dir(), 'damis_upload_');
                if ($temp_file === false) {
                    $this->addFlash('error', 'Could not create temporary file for upload.');
                    return $this->redirect($request->headers->get('referer'));
                }
                file_put_contents($temp_file, (string)$response2->getContent());

                $client = new \Guzzle\Http\Client($this->midasUrl);

                $session = $request->getSession();
                $sessionToken = $session->get('sessionToken');

                if (!$sessionToken) {
                    $this->addFlash('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
                    return $this->redirect($request->headers->get('referer'));
                }

                $post = [
                    'name' =>  preg_replace('/\\.[^.\\s]{3,4}$/', '', (string) $entity->getFile()['originalName']) . $id . '.' . $request->get('format'),
                    'parentFolderId' => json_decode((string) $request->get('path'), true)['idCSV'],
                    'size' => $entity->getFile()['size'],
                ];
                $req = $client->post('/action/file-explorer/file/init',
                    ['Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken],
                    json_encode($post)
                );

                try {
                    $response = json_decode((string) $req->send()->getBody(true), true);
                    if ($response['type'] == 'error') {
                        $this->addFlash('error', $this->translator->trans('MIDAS response', [], 'DatasetsBundle') . ': ' . $this->translator->trans($response["msgCodeTranslation"], [], 'DatasetsBundle'));
                        unlink($temp_file);
                        return $this->redirect($request->headers->get('referer'));
                    }

                    $fileId = $response['file']['id'];
                    $header = ['Content-Type: multipart/form-data', 'Authorization:' . $sessionToken];
                    $file = new CURLFile($temp_file, (string)$response2->headers->get('content-type'), preg_replace('/\\.[^.\\s]{3,4}$/', '', (string) $entity->getFile()['originalName']) . $id . '.' . $request->get('format'));
                    $fields = ['slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1];

                    $resource = curl_init();
                    curl_setopt($resource, CURLOPT_URL, $this->midasUrl . '/action/file-explorer/file/slice');
                    curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($resource, CURLOPT_POST, 1);
                    curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);
                    curl_exec($resource);
                    curl_close($resource);

                    $this->addFlash('success', $this->translator->trans('File uploaded successfully', [], 'DatasetsBundle'));
                } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                    $this->logger->error('Guzzle Error during Midas upload: ' . $e->getMessage());
                    $this->addFlash('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
                } finally {
                    unlink($temp_file); // Always clean up the temp file
                }
                return $this->redirect($request->headers->get('referer'));
            }
        } else {
            $workflow = $em->getRepository(Workflowtask::class)
                ->findOneBy(['experiment' => $request->get('experimentId'), 'taskBox' => $request->get('taskBox')]);
            if ($workflow) {
                $runtime = $workflow->getExecutionTime();
                $message = $workflow->getMessage();
            }
        }

        return $this->render('@DamisExperiment/component/technicalInformation.html.twig', [
            'id' => $id,
            'file' => $entity,
            'message' => $message,
            'runtime' => $runtime,
        ]);
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
        sscanf($memoryLimit, '%u%c', $number, $suffix);
        if (isset($suffix)) {
            $number = $number * 1024 ** strpos(' KMG', (string) $suffix);
        }
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)
            ->findOneBy(['user' => $user, 'datasetId' => $id]);
        if ($entity) {
            $format = explode('.', (string) $entity->getFile()['fileName']);
            $format = $format[count($format) - 1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'zip') {
                $zip = new ZipArchive();
                $res = $zip->open('./assets' . $entity->getFile()['fileName']); // Note: Relative paths are fragile
                $name = $zip->getNameIndex(0);
                if ($zip->numFiles > 1) {
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', [], 'DatasetsBundle'));
                    return false;
                }

                if ($res === true) {
                    $path = substr((string) $entity->getFile()['path'], 0, strripos((string) $entity->getFile()['path'], '/'));
                    $zip->extractTo('.' . $path, $name);
                    $zip->close();
                    $format = explode('.', $name);
                    $format = $format[count($format) - 1];
                    $fileReader = new ReadFile();
                    if ($format == 'arff') {
                        $dir = substr((string) $entity->getFile()['path'], 0, strripos((string) $entity->getFile()['path'], '.'));
                        $entity->setFilePath($dir . '.arff');
                        $rows = $fileReader->getRows('.' . $entity->getFilePath(), $format);
                        if ($rows === false) {
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                            $em->remove($entity);
                            $em->flush();
                            unlink('.' . $path . '/' . $name);
                            return false;
                        }
                        unset($rows);
                        $em->persist($entity);
                        $em->flush();
                        rename('.' . $path . '/' . $name, '.' . $dir . '.arff');
                        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', [], 'DatasetsBundle'));
                        return true;
                    } elseif ($format == 'txt' || $format == 'tab' || $format == 'csv') {
                        $rows = $fileReader->getRows('.' . $path . '/' . $name, $format);
                        if ($rows === false) {
                            $em->remove($entity);
                            $em->flush();
                            unlink('.' . $path . '/' . $name);
                            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', [], 'DatasetsBundle'));
                            return false;
                        }
                        unlink('.' . $path . '/' . $name);
                    } elseif ($format == 'xls' || $format == 'xlsx') {
                        $objPHPExcel = PHPExcel_IOFactory::load('.' . $path . '/' . $name);
                        $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                        array_unshift($rows, null);
                        unlink('.' . $path . '/' . $name);
                        unset($rows[0]);
                    } else {
                        $em->remove($entity);
                        $em->flush();
                        unlink('.' . $path . '/' . $name);
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset has wrong format!', [], 'DatasetsBundle'));
                        return false;
                    }
                }
            } elseif ($format == 'arff') {
                $entity->setFilePath($entity->getFile()['path']);
                if (memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number) {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    return false;
                }
                // unset($rows); // $rows not defined here
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFilePath(), $format);
                if ($rows === false) {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                    $em->remove($entity);
                    $em->flush();
                    unlink('.' . $entity->getFile()['fileName']);
                    return false;
                }
                unset($rows);
                $em->persist($entity);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', [], 'DatasetsBundle'));
                return true;
            } elseif ($format == 'txt' || $format == 'tab' || $format == 'csv') {
                $fileReader = new ReadFile();
                if (memory_get_usage(true) + $entity->getFile()['size'] * 5.8 > $number) {
                    $em->remove($entity);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Dataset is too large!', [], 'DatasetsBundle'));
                    return false;
                }
                $rows = $fileReader->getRows('./assets' . $entity->getFile()['fileName'], $format);
            } elseif ($format == 'xls' || $format == 'xlsx') {
                $objPHPExcel = PHPExcel_IOFactory::load('./assets' . $entity->getFile()['fileName']);
                $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                array_unshift($rows, null);
                unset($rows[0]);
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return false;
            }
            $hasHeaders = false;
            if (!empty($rows)) {
                // Check if $rows[1] exists, it might be an empty file
                if (isset($rows[1])) {
                    foreach ($rows[1] as $header) {
                        if (!(is_numeric($header))) {
                            $hasHeaders = true;
                        }
                    }
                }
            }
            $arff = '';
            $arff .= '@relation ' . $filename . PHP_EOL;
            if ($hasHeaders) {
                foreach ($rows[1] as $key => $header) {
                    // Remove spaces in header, to fit arff format
                    $header = preg_replace('/\s+/', '_', (string) $header);

                    // Check string is numeric or normal string
                    if (isset($rows[2][$key]) && is_numeric($rows[2][$key])) {
                        if (is_int($rows[2][$key] + 0)) {
                            $arff .= '@attribute ' . $header . ' ' . 'integer' . PHP_EOL;
                        } elseif (is_float($rows[2][$key] + 0)) {
                            $arff .= '@attribute ' . $header . ' ' . 'real' . PHP_EOL;
                        }
                    } else {
                        $arff .= '@attribute ' . $header . ' ' . 'string' . PHP_EOL;
                    }
                }
            } else {
                if (isset($rows[1])) { // Check if $rows[1] exists
                    foreach ($rows[1] as $key => $header) {
                        if (isset($rows[2][$key]) && is_numeric($rows[2][$key])) {
                            if (is_int($rows[2][$key] + 0)) {
                                $arff .= '@attribute ' . 'attr' . $key . ' ' . 'integer' . PHP_EOL;
                            } elseif (is_float($rows[2][$key] + 0)) {
                                $arff .= '@attribute ' . 'attr' . $key . ' ' . 'real' . PHP_EOL;
                            }
                        } else {
                            $arff .= '@attribute ' . 'attr' . $key . ' ' . 'string' . PHP_EOL;
                        }
                    }
                }
            }
            $arff .= '@data' . PHP_EOL;
            if ($hasHeaders) {
                unset($rows[1]);
            }
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    if ($key > 0) {
                        $arff .= ',' . $value;
                    } else {
                        $arff .= $value;
                    }
                }
                $arff .= PHP_EOL;
            }
            $dir = substr((string) $entity->getFile()['path'], 0, strripos((string) $entity->getFile()['path'], '.'));
            $projectDir = $this->getParameter('kernel.project_dir');
            $filePath = $projectDir . '/public' . $dir . ".arff";

            $fp = fopen($filePath, "w+");
            if ($fp === false) {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Could not write file!', [], 'DatasetsBundle'));
                return false;
            }
            fwrite($fp, $arff);
            fclose($fp);
            $entity->setFilePath($dir . ".arff");
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Dataset successfully uploaded!', [], 'DatasetsBundle'));
            return true;
        }
        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error!', [], 'DatasetsBundle'));
        return false;
    }
}