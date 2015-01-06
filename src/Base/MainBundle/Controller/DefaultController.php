<?php

namespace Base\MainBundle\Controller;

use Base\UserBundle\Entity\User;
use Guzzle\Http\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="base_main_default_index")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/midaslogin.html", name="midas_login")
     * @Method({"POST"})
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $sessionToken = $request->get('sessionToken', null);
		$sessionFinishDate = $request->get('sessionFinishDate', null);
		$name = $request->get('name', null);
		$surname = $request->get('surName', null);
		$userEmail = $request->get('email', null);
		$userId = $request->get('userId', null);
		$timeStamp = $request->get('timeStamp', null);
		$signature = $request->get('signature', null);
		
        $fp = fopen ($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/public.key.cer","r");
        $pubKey = fread($fp, filesize($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/public.key.cer"));
        fclose($fp);
        $key = openssl_get_publickey($pubKey);
        $details = openssl_pkey_get_details($key);
        openssl_public_decrypt(base64_decode($signature, true), $decriptedSignature, $details['key']);
		$tmpSignature = $timeStamp . $name . $surname . $sessionFinishDate . $userEmail . $sessionToken . $userId;

        if(!$sessionToken || !$signature || ($tmpSignature !== $decriptedSignature)){
            // Unset older session data 
            $this->get("security.context")->setToken(null);
			/*
            $post = [
                'sourceUrl' => 'http://damis.lt/midaslogin.html',
                'sessionToken' => 'trh6g6afhs5cmpmppd4vgio26k',
                'timeStamp' => time()
            ];
            $tmp = $post['sourceUrl'] . $post['timeStamp'];
            openssl_public_decrypt($tmp, $encriptedSignature, $details['key']);
            //var_dump($encriptedSignature); die;
            $client = new Client($this->container->getParameter('midas_url'));
            $req = $client->post('/web/action/login', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), $post);
            try {
              //  $req->send()->getBody(true);
            } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                var_dump('Error! ' . $e->getMessage());
            }
			*/
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('MIDAS user login request parameters are wrong!', array(), 'general'));
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $em->getRepository('BaseUserBundle:User')->findOneBy(array('userId' => $userId));

        if(!$user){
            if($userEmail){
                /* @var $emailExist \Base\UserBundle\Entity\User */;
                $emailExist = $em->getRepository('BaseUserBundle:User')->findOneBy(array('email' => $userEmail));
                if($emailExist){
                    // Remove older user with same email  if this user was form MIDAS
                    if ($emailExist->getUserId() > 0) {
                        // Remove user datasets
                        $files = $em->getRepository('DamisDatasetsBundle:Dataset')->findByUserId($emailExist->getId());
                        foreach($files as $file){
                            if($file){
                                if(file_exists('.' . $file->getFilePath()))
                                    if($file->getFilePath())
                                        unlink('.' . $file->getFilePath());
                                $em->remove($file);
                                $em->flush();
                            }
                        }
                        // Remove Eksperiments
                        $experiments = $em->getRepository('DamisExperimentBundle:Experiment')->findByUser($emailExist->getId());
                        foreach($experiments as $experiment){
                            if($experiment){
                                $em->remove($experiment);
                                $em->flush();
                            }
                        }                        
                        $em->remove($emailExist);
                        $em->flush();
                    } else {
                        $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('User with this email already exists!', array(), 'general'));
                        return $this->redirect($this->generateUrl('fos_user_security_login'));
                    }
                }
            }
            $user = new User();
            $user->setPassword($userEmail);
        }
        $user->setName($name);
        $user->setSurname($surname);
        if(!$userEmail)
            $userEmail = $userId . 'user@midas.lt';
         /* @var $emailExist \Base\UserBundle\Entity\User */;
        $emailExist = $em->getRepository('BaseUserBundle:User')->findOneBy(array('email' => $userEmail));
        if(!$emailExist){
            $user->setEmail($userEmail);
        }        
        $user->setUserId($userId);
        if(!$user->hasRole('ROLE_CONFIRMED'))
            $user->addRole('ROLE_CONFIRMED');
        $user->setUsername($userEmail);
        $em->persist($user);
        $em->flush();
        $session = $request->getSession();
        $session->set('sessionToken', $sessionToken);
        $token = new UsernamePasswordToken($user, null, "main", $user->getRoles());
        $this->get("security.context")->setToken($token);

        $request = $this->get("request");
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        return $this->redirect($this->generateUrl('experiments_history'));
    }

    /**
     * @Route("/midaslogout.html", name="midas_logout")
     * @Method("GET")
     * @Template()
     */
    public function logoutAction(Request $request)
    {
        $session = $request->getSession();
        if($session->has('sessionToken'))
            $sessionToken = $session->get('sessionToken');
        else {
            return $this->redirect($this->generateUrl('fos_user_security_logout'));
        }
        $client = new Client($this->container->getParameter('midas_url'));
        $req = $client->delete('/web/action/authentication/session/' . $sessionToken , array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), array());
        try {
            $data = json_decode($req->send()->getBody(true), true);
            if($data['type'] == 'success'){
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('Logged out successfully', array(), 'general'));
                $session->remove('sessionToken');
            } else {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Error when logging out', array(), 'general'));
            }
            return $this->redirect($this->generateUrl('fos_user_security_logout'));
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            var_dump('Error! ' . $e->getMessage()); die;
        }

    }

    /**
     * @Route("/lt.html", name="change_locale_lt")
     * @Method("GET")
     * @Template()
     */
    public function localeLtAction(Request $request)
    {
        $request->getSession()->set('_locale', 'lt');
        $locale = $request->getLocale();
        $request->setLocale('lt');
        return $this->redirect($this->get('request')->headers->get('referer'));
    }

    /**
     * @Route("/en.html", name="change_locale_en")
     * @Method("GET")
     * @Template()
     */
    public function localeEnAction(Request $request){
        $request->getSession()->set('_locale', 'en');
        $locale = $request->getLocale();
        $request->setLocale('en');
        return $this->redirect($this->get('request')->headers->get('referer'));
    }
}
