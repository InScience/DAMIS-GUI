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
        $data = $request->request->all(); var_dump($data); die;
        $data = [
            'sessionToken' => 1,
            'sessionFinishDate' => '2014-10-13',
            'name' => 'Deividas',
            'surName' => 'Jankauskas',
            'userEmail' => 'deividasjank3@gmail.com',
            'userId' => 'midas1',
            'timeStamp' => time(),
            'signature' => 'sadasda'
        ];
        $sessionToken = $data['sessionToken'];
        $sessionFinishDate = $data['sessionFinishDate'];
        $name = $data['name'];
        $surname = $data['surName'];
        $userEmail = $data['userEmail'];
        $userId = $data['userId'];
        $timeStamp = $data['timeStamp'];
        $signature = $data['signature'];

        $fp = fopen ($this->get('kernel')->getRootDir() . '/../' . "/src/Base/MainBundle/Resources/config/ssomidas.cer","r");
        $pubKey = fread($fp,8192);
        fclose($fp);
        openssl_get_publickey($pubKey);
        /*
        * NOTE:  Here you use the $pub_key value (converted, I guess)
        */
        openssl_public_decrypt($signature, $decriptedSignature, $pubKey);
        //@todo check data
        if(false){

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
    } /**
     * @Route("/midaslogin2.html", name="midas_login")
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
