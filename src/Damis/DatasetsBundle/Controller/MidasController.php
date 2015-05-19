<?php

namespace Damis\DatasetsBundle\Controller;

use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CURLFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class MidasController extends Controller
{
   /**
    *
    * @var \Symfony\Component\HttpFoundation\Session\Session 
    */
   private $session;
   
   /**
    *
    * @var \Container
    */
   protected $container;
           
    
   /**
    * @param \Symfony\Component\HttpFoundation\Session\Session $session
    * @param \Container $container
    */
    public function __construct(Session $session, Container $container)
    {
       $this->session = $session;
       $this->container = $container;
       
    }

    /**
     * This function reconnect MIDAS user if connection was lost
     * Function is not workig MIDAS becouse of MIDAS specification misleedings
     * 
     * @todo Fix or remove this functionality
     * @return int
     */
    public function relogin()
    {
        return 0;
/*        
        // 2015-02-06T13:12:52VardasadminPavardeadmin2015-02-06T13:31:04vardenis.pavardenis@ittc.vu.lt1i62bqims8oj0vh04b2n12b7nk750
        $sourceUrl = $this->container->getParameter('project_full_host') . '/midaslogin.html';
        $sourceUrl = 'http://193.219.89.37:8080/midaslogin.html';
        $timeStamp = date("Y-m-d H:i:s");
        $serviceProviderCode = $this->container->getParameter('service_provider_code');
        $sessionToken = '';
        if($this->session->has('sessionToken'))
            $sessionToken = $this->session->get('sessionToken');
        else {
            $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans('Midas login session is over. Please login to MIDAS and try again.', array(), 'DatasetsBundle'));
            return 0;
        }
        
echo        $toSign = $sourceUrl . $sessionToken . $timeStamp . $serviceProviderCode;
        
        $client = new Client($this->container->getParameter('midas_url'));
*/        
        /* @var $user \Base\UserBundle\Entity\User */
/*        $user = $this->getUser();
        // Check it is MIDAS user
        if ($user && $user->getUserId()) {
            // read DAMIS private key
            $fp = fopen ($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/damis.private.key","r");
            $pubKey = fread($fp, filesize($this->get('kernel')->getRootDir() . '/../' . "//src/Base/MainBundle/Resources/config/damis.private.key"));
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
            $req = $client->post('/action/authentication/sso', array('Content-Type' => 'multipart/form-data;charset=utf-8'), $post);
            var_dump($req);
            //
            // What is signed
            //$tmpSignature = $timeStamp . $name . $surname . $sessionFinishDate . $userEmail . $sessionToken . $userId;            
            
        } else {
            return 0;
        }
*/        
    }
    
    /**
     * Save file to temporal user MIDAS directory
     * 
     * @param string $filePath - path to real file which will be sent do MIDAS
     * @param string $fileMimeType - mime type of file
     * @param string $fileName name of file in post
     */
    public function saveInTempDir($filePath, $fileMimeType, $fileName)
    {
        $client = new Client($this->container->getParameter('midas_url'));
        
        if($this->session->has('sessionToken'))
            $sessionToken = $this->session->get('sessionToken');
        else {
            $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans('Midas login session is over. Please login to MIDAS and try again.', array(), 'DatasetsBundle'));
            return 0;
        }
        
        // receive user_temp directory id
        $emptyPost = array(
            'page' => 1,
            'pageSize' => 10,
            'uuid' => '',
            "orderBy" => "name",
            "sortingOrder" => "asc"
        );
        
        try {
            $req = $client->post('/action/research/folders', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($emptyPost));
            $response = json_decode($req->send()->getBody(true), true);
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans('Error when getting temporal directory id', array(), 'DatasetsBundle'));
            return 0;
        }
        // Get user_temp dir Id
        if (isset($response['details']['folderDetailsList'])) {
            $tempDirId = 0;
            foreach ($response['details']['folderDetailsList'] as $nr => $folder) {
                if (isset($folder['repositoryType']) && $folder['repositoryType'] == 'user_temp')
                    $tempDirId = $folder['resourceId'];
            }
        } else {
            $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans('Error when getting temporal directory id', array(), 'DatasetsBundle'));
            return 0;
        } 
        
        // File Uploading
        $post = array(
            'name' =>  'robots.txt',
            'parentFolderId' => $tempDirId,
            'size' => filesize($filePath)
        );
        try {
            // Initialization
            $req = $client->post('/action/file-explorer/file/init', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), json_encode($post));
            $response = json_decode($req->send()->getBody(true), true);

            if($response['type'] == 'error'){
                $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans($response["msgCode"], array(), 'DatasetsBundle'));
                return 0;
            }

            $fileId = $response['file']['id'];
            $header = array('Content-Type: multipart/form-data', 'Authorization:' . $sessionToken);

            $file = new CURLFile($filePath, $fileMimeType, $fileName);

            $fields = array('slice' => $file, 'fileId' => $fileId, 'sliceNo' => 1);

            $resource = curl_init();
            curl_setopt($resource, CURLOPT_URL, $this->container->getParameter('midas_url') . '/action/file-explorer/file/slice');
            curl_setopt($resource, CURLOPT_HTTPHEADER, $header);
            curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($resource, CURLOPT_POST, 1);
            curl_setopt($resource, CURLOPT_POSTFIELDS, $fields);

            $result = curl_exec($resource);

            curl_close($resource);

            $this->session->getFlashBag()->add('success', $this->container->get('translator')->trans('Not enough space in main directory. File uploaded successfully to your temporal MIDAS directory', array(), 'DatasetsBundle'));
            return 1;            
            
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $this->session->getFlashBag()->add('error', $this->container->get('translator')->trans('Error uploading file', array(), 'DatasetsBundle'));
            return 0;
        }
    }    
}