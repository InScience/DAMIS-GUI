<?php

namespace Damis\DatasetsBundle\Controller;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface; // Added
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use CURLFile;
use Psr\Log\LoggerInterface;

class MidasController extends AbstractController
{

    /**
     * @param SessionInterface $session
     * @param TranslatorInterface $translator
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly SessionInterface $session,
        private readonly TranslatorInterface $translator, 
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * This function reconnect MIDAS user if connection was lost
     * Function is not working MIDAS because of MIDAS specification misleadings
     *
     * @todo Fix or remove this functionality
     * @return int
     */
    public function relogin()
    {
        return 0;    
        
        $sourceUrl = $this->getParameter('project_full_host') . '/midaslogin.html';
        
        $timeStamp = date("Y-m-d H:i:s");
        $serviceProviderCode = $this->getParameter('service_provider_code');
        $sessionToken = '';

        if($this->session->has('sessionToken'))
            $sessionToken = $this->session->get('sessionToken');
        else {
            // Use injected $this->translator
            $this->session->getFlashBag()->add('error', $this->translator->trans('Midas login session is over. Please login to MIDAS and try again.', array(), 'DatasetsBundle'));
            return 0;
        }

        echo        $toSign = $sourceUrl . $sessionToken . $timeStamp . $serviceProviderCode;

        $client = new Client([
            'base_uri' => $this->getParameter('midas_url'),
            'verify'   => false,
        ]);

        /* @var $user \Base\UserBundle\Entity\User */
        $user = $this->getUser();
        // Check it is MIDAS user
        if ($user && $user->getUserId()) {
            
            $projectDir = $this->getParameter('kernel.project_dir');
            $keyPath = $projectDir . "/src/Base/MainBundle/Resources/config/damis.private.key";

            $fp = fopen ($keyPath, "r");
            $pubKey = fread($fp, filesize($keyPath));
            fclose($fp);
            $signatureAlg = 'SHA1';  // also posible SHA1RSA

            $pkeyId = openssl_pkey_get_private($pubKey, '');
            // Sign with sertificate
            openssl_sign($toSign, $signature, $pkeyId, $signatureAlg);
            $signature = base64_encode($signature);

            $post = array(
                'sourceUrl' => $sourceUrl,
                'sessionToken' => $sessionToken,
                'timeStamp' => $timeStamp,
                'serviceProviderCode' => $serviceProviderCode,
                'signature' => $signature
            );

            echo http_build_query($post);            
            $post =  http_build_query ($post);
            $client->post('/action/authentication/sso', [
                'headers' => ['Content-Type' => 'multipart/form-data;charset=utf-8'],
                'body'    => $post,
                'http_errors' => false,
            ]);

        } else {
            return 0;
        }
    }

    /**
     * Action get reserch folders to extend MIDAS session
     *
     */
    public function checkSession()
    {
        if ($this->session->has('sessionToken') && !empty($this->session->get('sessionToken'))) {
            $sessionToken = $this->session->get('sessionToken');
        } else {
            // User is not from midas
            return 0;
        }
        
        $client = new Client([
            'base_uri' => $this->getParameter('midas_url'),
            'verify'   => false,
        ]);
        
        // receive user_temp directory id
        $emptyPost = ['page' => 1, 'pageSize' => 10, 'uuid' => 'research', "orderBy" => "name", "sortingOrder" => "asc"];
        
        try {
            $req = $client->post('/action/research/folders', [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'authorization' => $sessionToken,
                ],
                'json' => $emptyPost,
                'http_errors' => true,
            ]);
            $response = json_decode((string) $req->getBody(), true);
            $this->logger?->info('MIDAS checkSession success', ['response' => $response]);
        } catch (RequestException $e) {
            $body = $e->getResponse()?->getBody();
            $response = $body ? json_decode((string) $body, true) : null;
            $this->logger?->warning('MIDAS checkSession failed', [
                'error' => $e->getMessage(),
                'response_body' => $response,
                'status' => $e->getResponse()?->getStatusCode()
            ]);
            if (!empty($response['msgCodeTranslation'])) {
                $this->session->getFlashBag()->add(
                    'error',
                    // Use $this->translator
                    $this->translator->trans('MIDAS response', [], 'DatasetsBundle').': '.$this->translator->trans($response['msgCodeTranslation'], [], 'DatasetsBundle')
                );
            } else {
                $this->session->getFlashBag()->add('error', $this->translator->trans('MIDAS session was lost', [], 'DatasetsBundle'));
                $this->session->set('sessionToken', null);
            }
            return 0;
        }
    }
    
    /**
     * Save file to temporal user MIDAS directory
     *
     * @param string $filePath     - path to real file which will be sent do MIDAS
     * @param string $fileMimeType - mime type of file
     * @param string $fileName     name of file in post
     */
    public function saveInTempDir($filePath, $fileMimeType, $fileName)
    {
        $client = new Client([
            'base_uri' => $this->getParameter('midas_url'),
            'verify'   => false,
        ]);
        
        if ($this->session->has('sessionToken')) {
            $sessionToken = $this->session->get('sessionToken');
        } else {
            // Use $this->translator
            $this->session->getFlashBag()->add('error', $this->translator->trans('Midas login session is over. Please login to MIDAS and try again.', [], 'DatasetsBundle'));
            return 0;
        }
        
        // receive user_temp directory id
        $emptyPost = ['page' => 1, 'pageSize' => 10, 'uuid' => '', "orderBy" => "name", "sortingOrder" => "asc"];
        
        try {
            $req = $client->post('/action/research/folders', [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'authorization' => $sessionToken,
                ],
                'json' => $emptyPost,
                'http_errors' => true,
            ]);
            $response = json_decode((string) $req->getBody(), true);
            $this->logger?->info('MIDAS saveInTempDir folders success', ['response' => $response]);
        } catch (RequestException $e) {
            $this->logger?->error('MIDAS saveInTempDir folders failed', [
                'error' => $e->getMessage(),
                'status' => $e->getResponse()?->getStatusCode(),
            ]);
            // Use $this->translator
            $this->session->getFlashBag()->add('error', $this->translator->trans('Error when getting temporal directory id', [], 'DatasetsBundle'));
            return 0;
        }
        // Get user_temp dir Id
        if (isset($response['details']['folderDetailsList'])) {
            $tempDirId = 0;
            foreach ($response['details']['folderDetailsList'] as $nr => $folder) {
                if (isset($folder['repositoryType']) && $folder['repositoryType'] == 'user_temp') {
                    $tempDirId = $folder['resourceId'];
                }
            }
        } else {
            $this->session->getFlashBag()->add('error', $this->translator->trans('Error when getting temporal directory id', [], 'DatasetsBundle'));
            return 0;
        }
        
        // File Uploading
        $post = ['name' => $fileName, 'parentFolderId' => $tempDirId, 'size' => filesize($filePath)];
        try {
            // Initialization
            $req = $client->post('/action/file-explorer/file/init', [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'authorization' => $sessionToken,
                ],
                'json' => $post,
                'http_errors' => true,
            ]);
            $response = json_decode((string) $req->getBody(), true);
            $this->logger?->info('MIDAS file init response', ['response' => $response]);

            if (($response['type'] ?? '') === 'error') {
                $this->session->getFlashBag()->add('error', $this->translator->trans('MIDAS response', [], 'DatasetsBundle').': '.$this->translator->trans($response["msgCodeTranslation"] ?? 'unknown', [], 'DatasetsBundle'));
                return 0;
            }

            $fileId = $response['file']['id'] ?? null;
            if (!$fileId) {
                $this->session->getFlashBag()->add('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
                return 0;
            }

            $header = ['Content-Type: multipart/form-data', 'Authorization:'.$sessionToken];

            $file = new CURLFile($filePath, $fileMimeType, $fileName);

            $fields = ['slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1];

            $resource = curl_init();
            // Use $this->getParameter()
            curl_setopt($resource, CURLOPT_URL, rtrim((string) $this->getParameter('midas_url'), '/').'/action/file-explorer/file/slice');
            curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
            curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($resource, CURLOPT_POST, 1);
            curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

            curl_exec($resource);
            $curlErr = curl_error($resource);
            $curlStatus = curl_getinfo($resource);
            curl_close($resource);
            if ($curlErr) {
                $this->logger?->error('MIDAS slice upload curl error', ['error' => $curlErr, 'info' => $curlStatus]);
            } else {
                $this->logger?->info('MIDAS slice upload success', ['info' => $curlStatus]);
            }

            $this->session->getFlashBag()->add('success', $this->translator->trans('Not enough space in main directory. File uploaded successfully to your temporal MIDAS directory', [], 'DatasetsBundle'));
            return 1;
            
        } catch (RequestException) {
            $this->session->getFlashBag()->add('error', $this->translator->trans('Error uploading file', [], 'DatasetsBundle'));
            return 0;
        }
    }
}