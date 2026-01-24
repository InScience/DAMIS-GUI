<?php

namespace Base\MainBundle\Controller;

use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Entity\Experiment;
use GuzzleHttp\Exception\RequestException;
use Base\UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly TranslatorInterface $translator, private readonly TokenStorageInterface $tokenStorage, private readonly EventDispatcherInterface $eventDispatcher, private readonly KernelInterface $kernel, private readonly ParameterBagInterface $params, private readonly RequestStack $requestStack)
    {
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/', name: 'base_main_default_index')]
    public function index(): Response
    {
        return $this->render('@BaseMain/Default/index.html.twig');
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/midaslogin.html', name: 'midas_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $sessionToken = $request->get('sessionToken', null);
        $sessionFinishDate = $request->get('sessionFinishDate', null);
        $name = $request->get('name', null);
        $surname = $request->get('surName', null);
        $userEmail = $request->get('email', null);
        $userId = $request->get('userId', null);
        $timeStamp = $request->get('timeStamp', null);
        $signature = $request->get('signature', null);

        // DEV BYPASS START
        $skipSignature = true;
        // DEV BYPASS END

        $fp = fopen($this->kernel->getProjectDir()."/src/Base/MainBundle/Resources/config/public.key.cer", "r");
        $pubKey = fread($fp, filesize($this->kernel->getProjectDir()."/src/Base/MainBundle/Resources/config/public.key.cer"));
        fclose($fp);
        $signatureAlg = 'SHA256';
        
        $tmpSignature = $timeStamp.$name.$surname.$sessionFinishDate.$userEmail.$sessionToken.$userId;
        
        $key = openssl_get_publickey($pubKey);
        $details = openssl_pkey_get_details($key);
        
        if (!$skipSignature && (!$sessionToken || !$signature || !openssl_verify($tmpSignature, base64_decode((string) $signature, true), $pubKey, $signatureAlg))) {
            $this->tokenStorage->setToken(null);
            $this->addFlash('error', $this->translator->trans('MIDAS user login request parameters are wrong!', [], 'general'));
            return $this->redirectToRoute('fos_user_security_login');
        }

        /* @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['userId' => $userId]);

        $isUserNew = false;
        if (!$user) {
            if ($userEmail) {
                /* @var $emailExist \Base\UserBundle\Entity\User */
                $emailExist = $this->em->getRepository(User::class)->findOneBy(['email' => $userEmail]);
                if ($emailExist) {
                    if ($emailExist->getUserId() > 0) {
                        // Remove user datasets
                        $files = $this->em->getRepository(Dataset::class)->findByUserId($emailExist->getId());
                        foreach ($files as $file) {
                            if ($file) {
                                if (file_exists('.'.$file->getFilePath())) {
                                    if ($file->getFilePath()) {
                                        unlink('.'.$file->getFilePath());
                                    }
                                }
                                $this->em->remove($file);
                                $this->em->flush();
                            }
                        }
                        // Remove Experiments
                        $experiments = $this->em->getRepository(Experiment::class)->findByUser($emailExist->getId());
                        foreach ($experiments as $experiment) {
                            if ($experiment) {
                                $this->em->remove($experiment);
                                $this->em->flush();
                            }
                        }
                        $this->em->remove($emailExist);
                        $this->em->flush();
                    } else {
                        $this->addFlash('error', $this->translator->trans('User with this email already exists!', [], 'general'));
                        return $this->redirectToRoute('fos_user_security_login');
                    }
                }
            }
            $user = new User();
            $user->setPassword($userEmail);
            $isUserNew = true;
        }
        
        $user->setName($name);
        $user->setSurname($surname);
        if (!$userEmail) {
            $userEmail = $userId.'user@midas.lt';
        }
        
        /* @var $emailExist \Base\UserBundle\Entity\User */
        $emailExist = $this->em->getRepository(User::class)->findOneBy(['email' => $userEmail]);
        if (!$emailExist) {
            $user->setEmail($userEmail);
        }
        $user->setUserId($userId);
        if (!$user->hasRole('ROLE_CONFIRMED')) {
            $user->addRole('ROLE_CONFIRMED');
        }
        $user->setUsername($userEmail);
        $this->em->persist($user);
        $this->em->flush();
        
        $session = $request->getSession();
        $session->set('sessionToken', $sessionToken);
        $token = new UsernamePasswordToken($user, "main", $user->getRoles());
        $this->tokenStorage->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);
        
        if ($isUserNew) {
            return $this->redirectToRoute('page_show', ['slug' => $this->params->get('first_time_page')]);
        } else {
            return $this->redirectToRoute('experiments_history');
        }
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/midaslogout.html', name: 'midas_logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        if ($session->has('sessionToken')) {
            $sessionToken = $session->get('sessionToken');
        } else
            return $this->redirectToRoute('fos_user_security_logout');
        
        
        $client = new Client(['base_uri' => $this->params->get('midas_url')]);

        try {
            $response = $client->delete('/action/authentication/session/'.$sessionToken, [
                'headers' => [
                    'Content-Type' => 'application/json;charset=utf-8',
                    'authorization' => $sessionToken
                ]
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            if ($data['type'] == 'success') {
                $this->addFlash('success', $this->translator->trans('Logged out successfully', [], 'general'));
                $session->remove('sessionToken');
            } else {
                $this->addFlash('error', $this->translator->trans('Error when logging out', [], 'general'));
            }
            return $this->redirectToRoute('fos_user_security_logout');
        } catch (RequestException) {
            $this->addFlash('error', $this->translator->trans('Error when logging out', [], 'general'));
            return $this->redirectToRoute('fos_user_security_logout');
        }
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/lt.html', name: 'change_locale_lt', methods: ['GET'])]
    public function localeLt(Request $request): Response
    {
        $request->getSession()->set('_locale', 'lt');
        $request->setLocale('lt');
        return $this->redirect($request->headers->get('referer'));
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/en.html', name: 'change_locale_en', methods: ['GET'])]
    public function localeEn(Request $request): Response
    {
        $request->getSession()->set('_locale', 'en');
        $request->setLocale('en');
        return $this->redirect($request->headers->get('referer'));
    }
}