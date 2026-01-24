<?php

namespace Damis\DatasetsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Damis\EntitiesBundle\Entity\Parametervalue;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use GuzzleHttp\Exception\BadResponseException;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Damis\DatasetsBundle\Entity\Dataset;
use Damis\DatasetsBundle\Form\Type\DatasetType;
use Base\ConvertBundle\Helpers\ReadFile;
use Symfony\Component\Form\FormError;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\File\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Iphp\FileStoreBundle\Mapping\Factory;
use Iphp\FileStoreBundle\FileStorage\FileStorageInterface;
use Psr\Log\LoggerInterface;


class DatasetsController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PaginatorInterface $paginator,
        private readonly ParameterBagInterface $params,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly ?Factory $mappingFactory = null,
        private readonly ?FileStorageInterface $fileStorage = null
    )
    {
    }

    /**
     * User datasets list window
     *
     * @param Request $request
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/list.html', name: 'datasets_list', methods: ['GET', 'POST'])]
    public function list(Request $request): Response
    {
        $sort = $request->get('order_by');
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        if ($sort == 'titleASC') {
            $entities = $em->getRepository(Dataset::class)
                ->getUserDatasets($user, ['title' => 'ASC']);
        } elseif ($sort == 'titleDESC') {
            $entities = $em->getRepository(Dataset::class)
                ->getUserDatasets($user, ['title' => 'DESC']);
        } elseif ($sort == 'createdASC') {
            $entities = $em->getRepository(Dataset::class)
                ->getUserDatasets($user, ['created' => 'ASC']);
        } elseif ($sort == 'createdDESC') {
            $entities = $em->getRepository(Dataset::class)
                ->getUserDatasets($user, ['created' => 'DESC']);
        } else {
            $entities = $em->getRepository(Dataset::class)->getUserDatasets($user);
        }

        $pagination = $this->paginator->paginate(
            $entities,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('@DamisDatasets/Datasets/list.html.twig', [
            'entities' => $pagination,
        ]);
    }


    /**
     * Delete datasets
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/delete.html', name: 'datasets_delete', methods: ['POST'])]
    public function delete(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $files = json_decode($request->request->get('file-delete-list'));
        $em = $this->doctrine->getManager();
        foreach ($files as $id) {
            $file = $em->getRepository(Dataset::class)->findOneByDatasetId($id);
            if ($file && ($file->getUser() == $user)) {
                $inUse = $em->getRepository(Parametervalue::class)->checkDatasets($id);
                if (!$inUse) {
                    $filePath = $this->getParameter('kernel.project_dir') . '/public' . $file->getFilePath();
                    if ($file->getFilePath() && file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $em->remove($file);
                } else {
                    $file->setHidden(true);
                    $em->persist($file);
                }
                $em->flush();
            }
        }

        return $this->redirectToRoute('datasets_list');
    }

    /**
     * Upload new dataset
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/new.html', name: 'datasets_new', methods: ['GET'])]
    public function new(): Response
    {
        $entity = new Dataset();
        $form = $this->createForm(DatasetType::class, $entity);

        return $this->render('@DamisDatasets/Datasets/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Create new dataset
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/create.html', name: 'datasets_create', methods: ['POST'])]
    public function create(Request $request)
    {
        $entity = new Dataset();
        $form = $this->createForm(DatasetType::class, $entity);
        $form->handleRequest($request);

        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form->get('file')->getData();

            if ($file) {
                $entity->setFile($file);
            }

            $entity->setDatasetCreated(time());
            $entity->setUser($user);
            $entity->setDatasetIsMidas(false);

            $em->persist($entity);
            $em->flush();

            return $this->uploadArff($request, $entity->getDatasetId());
        }

        return $this->render('@DamisDatasets/Datasets/new.html.twig', ['entity' => $entity, 'form'   => $form->createView()]);
    }

    /**
     * When uploading csv/txt/tab/xls/xlsx types to arff
     * convert it and save
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function uploadArff(Request $request, $id)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['user' => $user, 'datasetId' => $id]);

        if (!$entity) {
            $request->getSession()->getFlashBag()->add('error', 'Error! Could not find dataset entity.');
            return $this->redirectToRoute('datasets_new');
        }

        $fileData = $entity->getFile();
        if (!$fileData) {
            $request->getSession()->getFlashBag()->add('error', 'File data not found for dataset.');
            return $this->redirectToRoute('datasets_list');
        }

        // Handle different file data types
        if ($fileData instanceof UploadedFile) {
            // Handle UploadedFile object
            $fileName = $fileData->getClientOriginalName();
            $fullFilePath = $fileData->getRealPath();
            $format = strtolower($fileData->getClientOriginalExtension());

            // Move uploaded file to a permanent location
            $projectRoot = $this->getParameter('kernel.project_dir');
            $uploadDir = $projectRoot . '/public/uploads/datasets/';

            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = uniqid() . '_' . $fileName;
            $fileData->move($uploadDir, $newFileName);
            $fullFilePath = $uploadDir . $newFileName;
            $relativePath = '/uploads/datasets/' . $newFileName;

        } elseif (is_array($fileData)) {
            // Handle array format (from iphp/filestore-bundle)
            $fileName = $fileData['fileName'] ?? $fileData['originalName'] ?? 'unknown';
            $format = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));
            $projectRoot = $this->getParameter('kernel.project_dir');
            $relativePath = $fileData['path'];
            $fullFilePath = $projectRoot . '/public' . $relativePath;

            if (!file_exists($fullFilePath)) {
                // Try /web directory for backward compatibility
                $fullFilePath = $projectRoot . '/web' . $relativePath;
                if (!file_exists($fullFilePath)) {
                    $request->getSession()->getFlashBag()->add('error', 'Uploaded file could not be found at path: ' . $fullFilePath);
                    return $this->redirectToRoute('datasets_list');
                }
            }
        } else {
            // Unknown file data format
            $request->getSession()->getFlashBag()->add('error', 'Invalid file data format.');
            return $this->redirectToRoute('datasets_list');
        }

        $rows = [];

        try {
            if ($format == 'zip') {
                $request->getSession()->getFlashBag()->add('error', 'ZIP file processing is not fully implemented.');
                return $this->redirectToRoute('datasets_list');

            } elseif ($format == 'arff') {
                $entity->setFilePath($relativePath);
                $em->persist($entity);
                $em->flush();
                $request->getSession()->getFlashBag()->add('success', 'ARFF dataset successfully uploaded!');
                return $this->redirectToRoute('datasets_list');

            } elseif (in_array($format, ['txt', 'tab', 'csv'])) {
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows($fullFilePath, $format);

            } elseif (in_array($format, ['xls', 'xlsx'])) {
                $spreadsheet = IOFactory::load($fullFilePath);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                $rows = array_filter($rows, fn($row) => !empty(array_filter($row)));
            } else {
                $request->getSession()->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirectToRoute('datasets_list');
            }
        } catch (\Exception $e) {
            $request->getSession()->getFlashBag()->add('error', 'Error processing file: ' . $e->getMessage());
            $em->remove($entity);
            $em->flush();
            return $this->redirectToRoute('datasets_list');
        }

        if (empty($rows)) {
            $request->getSession()->getFlashBag()->add('error', 'Could not read any data from the uploaded file.');
            $em->remove($entity);
            $em->flush();
            return $this->redirectToRoute('datasets_list');
        }

        // ARFF Conversion Logic
        $hasHeaders = false;
        $firstRow = reset($rows);
        foreach ($firstRow as $cell) {
            if (!is_numeric($cell)) {
                $hasHeaders = true;
                break;
            }
        }

        $arff = '@RELATION ' . preg_replace('/\s+/', '_', (string) $entity->getDatasetTitle()) . PHP_EOL;
        $headerRow = $hasHeaders ? array_values(array_shift($rows)) : array_keys($firstRow);
        $firstDataRow = reset($rows);

        foreach ($headerRow as $key => $header) {
            $attributeName = $hasHeaders ? preg_replace('/[^\w\d_]/', '_', (string) $header) : 'attribute_' . $key;
            $sampleValue = $firstDataRow[$key] ?? null;
            $type = 'STRING';
            if (is_numeric($sampleValue)) {
                $type = (!str_contains($sampleValue, '.')) ? 'INTEGER' : 'REAL';
            }
            $arff .= '@ATTRIBUTE ' . $attributeName . ' ' . $type . PHP_EOL;
        }

        $arff .= '@DATA' . PHP_EOL;
        foreach ($rows as $row) {
            $arff .= implode(',', array_values($row)) . PHP_EOL;
        }

        // Save ARFF file
        $arffFileName = uniqid() . '_' . pathinfo((string) $fileName, PATHINFO_FILENAME) . ".arff";
        $uploadDir = $projectRoot . '/public/uploads/datasets/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $arffPath = $uploadDir . $arffFileName;
        file_put_contents($arffPath, $arff);

        $entity->setFilePath('/uploads/datasets/' . $arffFileName);
        $em->persist($entity);
        $em->flush();

        $request->getSession()->getFlashBag()->add('success', 'Dataset successfully uploaded and converted to ARFF!');
        return $this->redirectToRoute('datasets_list');
    }

    /**
     * Dataset upload handler for component form
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/upload_handler.html', name: 'dataset_upload_handler', methods: ['POST'])]
    public function uploadHandler(Request $request): Response
    {
        $entity = new Dataset();
        $form = $this->createForm(DatasetType::class, $entity);
        $form->handleRequest($request);
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            if ($file === null) {
                $form->get('file')->addError(new FormError($this->translator->trans('This value should not be blank.', [], 'validators')));
            } else {
                $entity->setFile($file);

                $em = $this->doctrine->getManager();
                $entity->setDatasetCreated(time());
                $entity->setUser($user);
                $entity->setDatasetIsMidas(false);
                $em->persist($entity);
                $em->flush();

                $this->uploadArff($request, $entity->getDatasetId());

                return $this->render('@DamisDatasets/Datasets/upload.html.twig', [
                    'form' => $form->createView(),
                    'file' => $entity,
                ]);
            }
        }

        return $this->render('@DamisDatasets/Datasets/upload.html.twig', [
            'form' => $form->createView(),
            'file' => null,
        ]);
    }

    /**
     * Upload new dataset from MIDAS
     *
     * @param Request $request
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/midasnew.html', name: 'datasets_midas_new', methods: ['GET'])]
    public function newMidas(Request $request): Response
    {
        $client = new Client();
        $midasBaseUrl = $this->params->get('midas_url');

        $notLogged = false;
        $session = $request->getSession();
        $sessionToken = $session->get('sessionToken', '');

        if (empty($sessionToken)) {
            $notLogged = true;
        }

        $page = $request->query->getInt('page', 1);
        $path = $request->query->get('path', '');
        $uuid = $request->query->get('uuid', 'research');
        $id = $request->query->get('id');

        $data = json_decode($request->query->get('data'));
        if ($data && !empty($data) && $request->query->get('edit') != 1) {
            $id = $data[0]->value;
            $decodedId = json_decode($id, true);
            $path = $decodedId['path'];
            $page = $decodedId['page'];

            $folders = explode('/', (string) $path);
            array_pop($folders); // Remove the last element
            $path = implode('/', $folders) . '/';
        }

        // Default path
        if (!$path) {
            $files = [
                'details' => [
                    'folderDetailsList' => [
                        [
                            'name' => $this->translator->trans('Published research', [], 'DatasetsBundle'),
                            'path' => 'publishedResearch',
                            'type' => 'RESEARCH',
                            'modifyDate' => time() * 1000,
                            'page' => 0,
                            'uuid' => 'publishedResearch',
                            'resourceId'   => '',
                        ],
                        [
                            'name' => $this->translator->trans('Not published research', [], 'DatasetsBundle'),
                            'path' => 'research',
                            'type' => 'RESEARCH',
                            'modifyDate' => time() * 1000,
                            'page' => 0,
                            'uuid' => 'research',
                            'resourceId'   => '',
                        ],
                    ],
                ],
            ];

            return $this->render('@DamisDatasets/Datasets/newMidas.html.twig', [
                'notLogged' => $notLogged,
                'files' => $files,
                'page' => 0,
                'pageCount' => 1,
                'totalFiles' => 0,
                'previous' => 0,
                'next' => 0,
                'path' => $path,
                'uuid' => '',
                'selected' => 0,
            ]);
        }

        $post = [
            'page' => $page,
            'pageSize' => 10,
            'uuid' => $uuid,
        ];
        $files = [];

        try {
            $response = $client->request('POST', $midasBaseUrl . '/action/research/folders', [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'authorization' => $sessionToken
                ],
                'json' => $post, // 'json' option automatically encodes the body and sets the correct header
                'http_errors' => true // This will cause an exception for 4xx/5xx responses
            ]);

            $files = json_decode($response->getBody()->getContents(), true);

        } catch (RequestException) {
            $notLogged = true; // Assume any failure is a login issue for simplicity
        }

        $pageCount = 0;
        $totalFiles = 0;
        if (isset($files['details'])) {
            $pageCount = $files['details']['pageCount'];
            $totalFiles= $files['details']['totalElements'];
            // Remove bad files
            $extensions = ['txt', 'tab', 'csv', 'xls', 'xlsx', 'arff', 'zip'];
            if (!empty($files['details']['folderDetailsList'])) {
                $files['details']['folderDetailsList'] = array_filter($files['details']['folderDetailsList'], function($item) use ($extensions) {
                    if ($item['type'] === 'FILE') {
                        return in_array(strtolower(pathinfo((string) $item['name'], PATHINFO_EXTENSION)), $extensions);
                    }
                    return true; // Keep folders
                });
            }
        }

        return $this->render('@DamisDatasets/Datasets/newMidas.html.twig', [
            'notLogged' => $notLogged,
            'files' => $files,
            'page' => $page,
            'pageCount' => $pageCount,
            'totalFiles' => $totalFiles,
            'previous' => $page - 1,
            'next' => $page + 1,
            'path' => $path,
            'uuid' => $uuid,
            'selected' => $id,
        ]);
    }

    /**
     * Create new midas dataset
     *
     * @param Request $request
     *
     * @return mixed
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/createmidas.html', name: 'datasets_create_midas', methods: ['POST'])]
    public function createMidas(Request $request): RedirectResponse
    {
        $em = $this->doctrine->getManager();
        $client = new Client(['base_uri' => $this->params->get('midas_url')]);
        $data = json_decode($request->request->get('dataset_pk'), true);
        if (!$data) {
            $request->getSession()->getFlashBag()->add('error', $this->translator->trans('File is not selected', [], 'DatasetsBundle'));
            return $this->redirectToRoute('datasets_midas_new');
        }
        $session = $request->getSession();
        if ($session->has('sessionToken')) {
            $sessionToken = $session->get('sessionToken');
        } else {
            $request->getSession()->getFlashBag()->add('error', $this->translator->trans('Error fetching file', [], 'DatasetsBundle'));
            return $this->redirectToRoute('datasets_midas_new');
        }

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
            $file->setDatasetCreated(time());
            $user = $this->getUser();
            $file->setUser($user);
            $file->setDatasetIsMidas(true);
            $tempFile = $this->params->get("kernel.cache_dir").'/../'.time().$data['name'];
            $em->persist($file);
            $em->flush();
            $fp = fopen($tempFile, "w");
            fwrite($fp, (string) $body);
            fclose($fp);

            $file2 = new File($tempFile);

            $refClass = new ReflectionClass(Dataset::class);

            if ($this->mappingFactory && $this->fileStorage) {
                $mapping = $this->mappingFactory->getMappingFromField($file, $refClass, 'file');
                $fileData = $this->fileStorage->upload($mapping, $file2);
            } else {
                // Fallback if services aren't available
                $request->getSession()->getFlashBag()->add('error', 'File storage service not configured');
                return $this->redirectToRoute('datasets_midas_new');
            }

            $orgFilename = basename((string) $data['name']);
            $fileData['originalName'] = $orgFilename;

            $file->setFile($fileData);
            $em->persist($file);
            $em->flush();
            unlink($tempFile);

            return $this->uploadArff($request, $file->getDatasetId());

        } catch (BadResponseException) {
            $request->getSession()->getFlashBag()->add('error', $this->translator->trans('Error fetching file', [], 'DatasetsBundle'));
            return $this->redirectToRoute('datasets_midas_new');
        }
    }

    /**
     * Edit dataset
     */
    #[Route('/{id}/edit.html', name: 'datasets_edit', methods: ['GET'])]
    public function edit($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $id]);

        if (!$entity || ($entity->getUser() != $user)) {
            $this->logger->error('Invalid try to access dataset by user id: '.$user->getId());
            return $this->redirectToRoute('datasets_list');
        }

        $form = $this->createForm(DatasetType::class, $entity);

        return $this->render('@DamisDatasets/Datasets/edit.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
            'id' => $id,
        ]);
    }

    /**
     * Update dataset
     *
     * @param Request $request
     * @param int     $id      Dataset id
     *
     * @return Response
     */
    #[Route('/{id}/update.html', name: 'datasets_update', methods: ['POST'])]
    public function update(Request $request, $id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['datasetId' => $id]);

        if (!$entity || ($entity->getUser() != $user)) {
            $this->logger->error('Invalid try to access dataset by user id: '.$user->getId());
            return $this->redirectToRoute('datasets_list');
        }

        $form = $this->createForm(DatasetType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newFile = $form->get('file')->getData();

            if ($newFile) {
                $entity->setFile($newFile);

                $entity->setDatasetUpdated(time());
                $em->flush();

                return $this->uploadArff($request, $entity->getDatasetId());
            }

            $entity->setDatasetUpdated(time());
            $em->flush();

            $this->addFlash('success', 'Dataset successfully updated!');

            return $this->redirectToRoute('datasets_list');
        }

        return $this->render('@DamisDatasets/Datasets/edit.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
        ]);
    }

    /**
     * Dataset upload component form
     *
     * @param Request $request
     *
     * @return Response
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/upload.html', name: 'dataset_upload')]
    public function upload(Request $request): Response
    {
        $user = $this->getUser();

        $entity = new Dataset();
        $form = $this->createForm(DatasetType::class, $entity);

        $dataset = null;
        $datasetUrlJson = $request->query->get('dataset_url');

        if ($datasetUrlJson) {
            $data = json_decode($datasetUrlJson);
            if (!empty($data) && isset($data[0]->value)) {
                $datasetId = $data[0]->value;
                $em = $this->doctrine->getManager();
                $dataset = $em->getRepository(Dataset::class)
                    ->findOneBy(['datasetId' => $datasetId, 'user' => $user]);
            }
        }

        return $this->render('@DamisDatasets/Datasets/upload.html.twig', [
            'form' => $form->createView(),
            'file' => $dataset,
        ]);
    }

    /**
     * Convert dataset to CSV format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_csv', name: 'convert_csv', methods: ['GET'])]
    public function convertCsv($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($filePath, 'arff');

        if (!$rows) {
            $this->addFlash('error', 'Could not read file');
            return $this->redirectToRoute('datasets_list');
        }

        $headers = [];
        $dataRows = [];
        $isData = false;

        foreach ($rows as $row) {
            if (empty($row)) continue;

            $firstToken = strtolower(trim($row[0] ?? ''));

            if (!$isData) {
                if (str_starts_with($firstToken, '@attribute')) {
                    $str = preg_replace('/\s+/i', " ", $firstToken);
                    $parts = explode(' ', trim($str));

                    if (count($parts) >= 2) {
                        $headers[] = $parts[1];
                    }
                } elseif (str_starts_with($firstToken, '@data')) {
                    $isData = true;
                }
            } else {
                if ($firstToken === '' || str_starts_with($firstToken, '%') || str_starts_with($firstToken, '@relation')) {
                    continue;
                }
                $dataRows[] = $row;
            }
        }

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers);
        foreach ($dataRows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.csv"');

        return $response;
    }

    /**
     * Convert dataset to ARFF format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_arff', name: 'convert_arff', methods: ['GET'])]
    public function convertArff($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $content = file_get_contents($filePath);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.arff"');

        return $response;
    }

    /**
     * Convert dataset to TXT format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_txt', name: 'convert_txt', methods: ['GET'])]
    public function convertTxt($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($filePath, 'arff');

        if (!$rows) {
            $this->addFlash('error', 'Could not read file');
            return $this->redirectToRoute('datasets_list');
        }

        $headers = [];
        $dataRows = [];
        $isData = false;

        foreach ($rows as $row) {
            if (empty($row)) continue;

            $firstToken = strtolower(trim($row[0] ?? ''));

            if (!$isData) {
                if (str_starts_with($firstToken, '@attribute')) {
                    $str = preg_replace('/\s+/i', " ", $firstToken);
                    $parts = explode(' ', trim($str));

                    if (count($parts) >= 2) {
                        $headers[] = $parts[1];
                    }
                } elseif (str_starts_with($firstToken, '@data')) {
                    $isData = true;
                }
            } else {
                if ($firstToken === '' || str_starts_with($firstToken, '%') || str_starts_with($firstToken, '@relation')) {
                    continue;
                }
                $dataRows[] = $row;
            }
        }

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers, ' ');
        foreach ($dataRows as $row) {
            fputcsv($fp, $row, ' ');
        }
        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.txt"');

        return $response;
    }

    /**
     * Convert dataset to TAB format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_tab', name: 'convert_tab', methods: ['GET'])]
    public function convertTab($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($filePath, 'arff');

        if (!$rows) {
            $this->addFlash('error', 'Could not read file');
            return $this->redirectToRoute('datasets_list');
        }

        $headers = [];
        $dataRows = [];
        $isData = false;

        foreach ($rows as $row) {
            if (empty($row)) continue;

            $firstToken = strtolower(trim($row[0] ?? ''));

            if (!$isData) {
                if (str_starts_with($firstToken, '@attribute')) {
                    $str = preg_replace('/\s+/i', " ", $firstToken);
                    $parts = explode(' ', trim($str));

                    if (count($parts) >= 2) {
                        $headers[] = $parts[1];
                    }
                } elseif (str_starts_with($firstToken, '@data')) {
                    $isData = true;
                }
            } else {
                if ($firstToken === '' || str_starts_with($firstToken, '%') || str_starts_with($firstToken, '@relation')) {
                    continue;
                }
                $dataRows[] = $row;
            }
        }

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers, "\t");
        foreach ($dataRows as $row) {
            fputcsv($fp, $row, "\t");
        }
        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.tab"');

        return $response;
    }

    /**
     * Convert dataset to XLS format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_xls', name: 'convert_xls', methods: ['GET'])]
    public function convertXls($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($filePath, 'arff');

        if (!$rows) {
            $this->addFlash('error', 'Could not read file');
            return $this->redirectToRoute('datasets_list');
        }

        $headers = [];
        $dataRows = [];
        $isData = false;

        foreach ($rows as $row) {
            if (empty($row)) continue;

            $firstToken = strtolower(trim($row[0] ?? ''));

            if (!$isData) {
                if (str_starts_with($firstToken, '@attribute')) {
                    $str = preg_replace('/\s+/i', " ", $firstToken);
                    $parts = explode(' ', trim($str));

                    if (count($parts) >= 2) {
                        $headers[] = $parts[1];
                    }
                } elseif (str_starts_with($firstToken, '@data')) {
                    $isData = true;
                }
            } else {
                if ($firstToken === '' || str_starts_with($firstToken, '%') || str_starts_with($firstToken, '@relation')) {
                    continue;
                }
                $dataRows[] = $row;
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        $columnIndex = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // Write data
        $rowIndex = 2;
        foreach ($dataRows as $row) {
            $columnIndex = 1;
            foreach ($row as $cell) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cell);
                $columnIndex++;
            }
            $rowIndex++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.xls"');

        return $response;
    }

    /**
     * Convert dataset to XLSX format
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/convert_xlsx', name: 'convert_xlsx', methods: ['GET'])]
    public function convertXlsx($id): Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneByDatasetId($id);

        if (!$entity || ($entity->getUser() != $user)) {
            throw $this->createNotFoundException('Dataset not found');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/public' . $entity->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'File not found');
            return $this->redirectToRoute('datasets_list');
        }

        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($filePath, 'arff');

        if (!$rows) {
            $this->addFlash('error', 'Could not read file');
            return $this->redirectToRoute('datasets_list');
        }

        $headers = [];
        $dataRows = [];
        $isData = false;

        foreach ($rows as $row) {
            if (empty($row)) continue;

            $firstToken = strtolower(trim($row[0] ?? ''));

            if (!$isData) {
                if (str_starts_with($firstToken, '@attribute')) {
                    $str = preg_replace('/\s+/i', " ", $firstToken);
                    $parts = explode(' ', trim($str));

                    if (count($parts) >= 2) {
                        $headers[] = $parts[1];
                    }
                } elseif (str_starts_with($firstToken, '@data')) {
                    $isData = true;
                }
            } else {
                if ($firstToken === '' || str_starts_with($firstToken, '%') || str_starts_with($firstToken, '@relation')) {
                    continue;
                }
                $dataRows[] = $row;
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        $columnIndex = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // Write data
        $rowIndex = 2;
        foreach ($dataRows as $row) {
            $columnIndex = 1;
            foreach ($row as $cell) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cell);
                $columnIndex++;
            }
            $rowIndex++;
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getDatasetTitle() . '.xlsx"');

        return $response;
    }
}