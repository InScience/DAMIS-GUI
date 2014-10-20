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
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function loginAction(Request $request)
    {
        $data = $request->request->all();
      /*  $data = [
            'sessionToken' => '3rcv2m8q60ebgbmmkoqcdvcbm',
            'sessionFinishDate' => '2014-10-13T14:32:17',
            'name' => 'Vardas123',
            'surName' => 'Pavarde123',
            'userEmail' => 'null',
            'userId' => 'midas1',
            'timeStamp' => '2014-10-13T14:14:17',
            'signature' => 'B14QJud0joY6GEjOZ0eh+t+O0QWDXXrD6ZEJ0hWC2LMfbP4CL4c3zIb7QRH9g05hGXYaWWczFPdFEsf+lGem4vn1LCNGGZN+fQkG0zCM3uyNqdW+Uui641/0KuxiaIU0Iz3SNvHJ9p3R/SVbj+2sk85MAHylrLfRp1WU22hZYvt2nMuT0cVroqUW+kJepjYkHd0DPS00ZYf3WzkuZKfjy90YGvEZxWOgtPhIYWh7NCqiu+TG3vVns2p7ThiX4qsw+TiSHUXmVVN1jOaHwAyIqDtTLKDK5mkmaTjtvuP2CA957CsId0084huE0Z6D7werKZgC9e+zDisb3bYtCpLs1w==',
        ];*/
        $sessionToken = $data['sessionToken'];
        $sessionFinishDate = $data['sessionFinishDate'];
        $name = $data['name'];
        $surname = $data['surName'];
        $userEmail = $data['userEmail'];
        $userId = $data['userId'];
        $timeStamp = $data['timeStamp'];
        $signature = $data['signature'];

        $fp = fopen ($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/public.key.cer","r");
        $pubKey = fread($fp, filesize($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/public.key.cer"));
        fclose($fp);
        $key = openssl_get_publickey($pubKey);
        $details = openssl_pkey_get_details($key);
        openssl_public_decrypt(base64_decode($signature, true), $decriptedSignature, $details['key']);
        $tmpSignature = $data['timeStamp'] . $data['name'] . $data['surName'] . $data['sessionFinishDate'] . $data['userEmail'] . $data['sessionToken'] . $data['userId'];

        if(!$tmpSignature === $decriptedSignature){
            $post = [
                'sourceUrl' => 'http://damis.lt/midaslogin.html',
                'sessionToken' => 'trh6g6afhs5cmpmppd4vgio26k',
                'timeStamp' => time()
            ];
            $tmp = $post['sourceUrl'] . $post['timeStamp'];
            openssl_public_decrypt($tmp, $encriptedSignature, $details['key']);
            //var_dump($encriptedSignature); die;
            $client = new Client('http://midas.insoft.lt:8888');
            $req = $client->post('/web/action/login', array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), array($post));
            try {
              //  $req->send()->getBody(true);
            } catch (\Guzzle\Http\Exception\BadResponseException $e) {
                var_dump('Error! ' . $e->getMessage());
            }
        }
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $em->getRepository('BaseUserBundle:User')->findOneBy(array('userId' => $userId));

        if(!$user){
            if($userEmail){
                $emailExist = $em->getRepository('BaseUserBundle:User')->findOneBy(array('email' => $userEmail));
                if($emailExist){
                    $this->get('session')->getFlashBag()->add('error', 'User with this email already exists');
                    return $this->redirect($this->generateUrl('base_main_default_index'));
                }
            }
            $user = new User();
            $user->setPassword($userEmail);
        }
        $user->setName($name);
        $user->setSurname($surname);
        if(!$userEmail)
            $userEmail = $userId . 'user@midas.lt';
        $user->setEmail($userEmail);
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
            return $this->redirect($this->generateUrl('base_main_default_index'));
        }
        $client = new Client('http://midas.insoft.lt:8887');
        $req = $client->delete('/web/action/authentication/session/' . $sessionToken , array('Content-Type' => 'application/json;charset=utf-8', 'authorization' => $sessionToken), array());
        try {
            $data = json_encode($req->send()->getBody(true), true);
            if($data['type'] == 'success'){
                $this->get('session')->getFlashBag()->add('success', 'Logged out successfully');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Error when logging out');
            }
            return $this->redirect($this->generateUrl('base_main_default_index'));
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
