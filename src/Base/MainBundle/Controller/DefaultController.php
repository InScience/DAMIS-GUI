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
        //$data = $request->request->all();
        $data = [
            'sessionToken' => '3rcv2m8q60ebgbmmkoqcdvcbm',
            'sessionFinishDate' => '2014-10-13T14:32:17',
            'name' => 'Vardas123',
            'surName' => 'Pavarde123',
            'userEmail' => 'null',
            'userId' => 'midas1',
            'timeStamp' => '2014-10-13T14:14:17',
            'signature' => 'B14QJud0joY6GEjOZ0eh+t+O0QWDXXrD6ZEJ0hWC2LMfbP4CL4c3zIb7QRH9g05hGXYaWWczFPdFEsf+lGem4vn1LCNGGZN+fQkG0zCM3uyNqdW+Uui641/0KuxiaIU0Iz3SNvHJ9p3R/SVbj+2sk85MAHylrLfRp1WU22hZYvt2nMuT0cVroqUW+kJepjYkHd0DPS00ZYf3WzkuZKfjy90YGvEZxWOgtPhIYWh7NCqiu+TG3vVns2p7ThiX4qsw+TiSHUXmVVN1jOaHwAyIqDtTLKDK5mkmaTjtvuP2CA957CsId0084huE0Z6D7werKZgC9e+zDisb3bYtCpLs1w==',
        ];
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
        $tmpSignature = $data['timeStamp'] . $data['name'] . $data['surName'] . $data['sessionFinishDate'] . $data['userEmail'] . $data['sessionToken'];

        if(!$tmpSignature === $decriptedSignature){
            $post = [
                'sourceUrl' => 'http://damis.lt/midaslogin.html',
                'sessionToken' => '',
                'timeStamp' => time()
            ];
            $tmp = $post['sourceUrl'] . $post['timeStamp'];
            openssl_public_decrypt($tmp, $encriptedSignature, $details['key']);
            //var_dump($encriptedSignature); die;
            $client = new Client('http://midas.insoft.lt:8888');
            $req = $client->post('/web/public-app.html#/login?sourceUrl=http:%2F%2Fd.damis.lt%2midaslogin.html', array(), array($post));
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
            $user = new User();
            $user->setPassword($userEmail);
        }
        $user->setName($name);
        $user->setSurname($surname);
        $user->setEmail($userEmail);
        $user->setUserId($userId);
        if(!$user->hasRole('ROLE_CONFIRMED'))
            $user->addRole('ROLE_CONFIRMED');
        $user->setUsername($userEmail);
        $em->persist($user);
        $em->flush();

        $token = new UsernamePasswordToken($user, null, "main", $user->getRoles());
        $this->get("security.context")->setToken($token);

        $request = $this->get("request");
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
        return $this->redirect($this->generateUrl('experiments_history'));
    }

    /**
     * @Route("/midaslogin2.html", name="midas_login2")
     * @Method("GET")
     * @Template()
     */
    public function login2Action(Request $request)
    {

        $client = new Client('http://midas.insoft.lt:8888');
        $req = $client->post('/web/public-app.html#/login?sourceUrl=http:%2F%2Fd.damis.lt%2Fapp_dev.php%2midaslogin.html', array(), array());
        try {
            $req->send()->getBody(true);
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            var_dump('Error! ' . $e->getMessage());
        } die;

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
