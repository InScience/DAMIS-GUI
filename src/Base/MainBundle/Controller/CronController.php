<?php

namespace Base\MainBundle\Controller;

use Base\MainBundle\Entity\CronJob;
use Base\MainBundle\Form\CronJobType;
use Base\MainBundle\Repository\CronJobRepository;
use Base\MainBundle\Service\CronTabManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Cron Manager Controller
 */
#[Route('/cron')]
class CronController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TranslatorInterface $translator;
    private CronJobRepository $cronJobRepository;
    private CronTabManager $cronTabManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        CronJobRepository $cronJobRepository,
        CronTabManager $cronTabManager
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->cronJobRepository = $cronJobRepository;
        $this->cronTabManager = $cronTabManager;
    }

    /**
     * Lists all cron jobs and shows the add form
     */
    #[Route('/', name: 'cron_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Check if user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get all cron jobs from database
        $cronJobs = $this->cronJobRepository->findAll();

        // Create form for adding new cron job
        $cronJob = new CronJob();
        $form = $this->createForm(CronJobType::class, $cronJob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle custom schedule
            $schedule = $form->get('schedule')->getData();
            if ($schedule === 'custom') {
                $customSchedule = $form->get('scheduleCustom')->getData();
                if ($customSchedule) {
                    $cronJob->setSchedule($customSchedule);
                } else {
                    $this->addFlash('error', $this->translator->trans('cron.flash.custom_schedule_required', [], 'general'));
                    return $this->redirectToRoute('cron_index');
                }
            }

            $this->entityManager->persist($cronJob);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('cron.flash.job_added', [], 'general'));
            
            // Sync to system crontab
            if ($this->cronTabManager->syncToSystemCrontab()) {
                $this->addFlash('info', $this->translator->trans('cron.flash.sync_success', [], 'general'));
            } else {
                $this->addFlash('warning', $this->translator->trans('cron.flash.sync_failed', [
                    '%error%' => $this->cronTabManager->getLastError()
                ], 'general'));
            }

            return $this->redirectToRoute('cron_index');
        }

        // Get system cron information
        $cronUser = $this->getCronUser();
        $cronRoot = $this->getCronRootDirectory();

        return $this->render('@BaseMain/Cron/index.html.twig', [
            'cron_jobs' => $cronJobs,
            'form' => $form->createView(),
            'cron_user' => $cronUser,
            'cron_root' => $cronRoot,
        ]);
    }

    /**
     * Show details of a specific cron job
     */
    #[Route('/{id}', name: 'cron_show', methods: ['GET'])]
    public function show(CronJob $cronJob): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('@BaseMain/Cron/show.html.twig', [
            'cron_job' => $cronJob,
        ]);
    }

    /**
     * Edit an existing cron job
     */
    #[Route('/{id}/edit', name: 'cron_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CronJob $cronJob): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(CronJobType::class, $cronJob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle custom schedule
            $schedule = $form->get('schedule')->getData();
            if ($schedule === 'custom') {
                $customSchedule = $form->get('scheduleCustom')->getData();
                if ($customSchedule) {
                    $cronJob->setSchedule($customSchedule);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('cron.flash.job_updated', [], 'general'));
            
            // Sync to system crontab
            if ($this->cronTabManager->syncToSystemCrontab()) {
                $this->addFlash('info', $this->translator->trans('cron.flash.sync_success', [], 'general'));
            } else {
                $this->addFlash('warning', $this->translator->trans('cron.flash.sync_failed', [
                    '%error%' => $this->cronTabManager->getLastError()
                ], 'general'));
            }

            return $this->redirectToRoute('cron_index');
        }

        return $this->render('@BaseMain/Cron/edit.html.twig', [
            'cron_job' => $cronJob,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Toggle enabled status of a cron job
     */
    #[Route('/{id}/toggle', name: 'cron_toggle', methods: ['POST'])]
    public function toggle(CronJob $cronJob): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $cronJob->setEnabled(!$cronJob->isEnabled());
        $this->entityManager->flush();

        $status = $cronJob->isEnabled() ? 'enabled' : 'disabled';
        $this->addFlash('success', $this->translator->trans('cron.flash.job_' . $status, [], 'general'));
        
        // Sync to system crontab
        if ($this->cronTabManager->syncToSystemCrontab()) {
            $this->addFlash('info', $this->translator->trans('cron.flash.sync_success', [], 'general'));
        } else {
            $this->addFlash('warning', $this->translator->trans('cron.flash.sync_failed', [
                '%error%' => $this->cronTabManager->getLastError()
            ], 'general'));
        }

        return $this->redirectToRoute('cron_index');
    }

    /**
     * Delete a cron job
     */
    #[Route('/{id}/delete', name: 'cron_delete', methods: ['POST'])]
    public function delete(Request $request, CronJob $cronJob): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $cronJob->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($cronJob);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('cron.flash.job_deleted', [], 'general'));
            
            // Sync to system crontab
            if ($this->cronTabManager->syncToSystemCrontab()) {
                $this->addFlash('info', $this->translator->trans('cron.flash.sync_success', [], 'general'));
            } else {
                $this->addFlash('warning', $this->translator->trans('cron.flash.sync_failed', [
                    '%error%' => $this->cronTabManager->getLastError()
                ], 'general'));
            }
        }

        return $this->redirectToRoute('cron_index');
    }

    /**
     * Export cron jobs to crontab format
     */
    #[Route('/export/crontab', name: 'cron_export', methods: ['GET'])]
    public function export(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $cronJobs = $this->cronJobRepository->findAllEnabled();

        $crontabContent = "# DAMIS Cron Jobs\n";
        $crontabContent .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($cronJobs as $cronJob) {
            if ($cronJob->getDescription()) {
                $crontabContent .= "# " . $cronJob->getDescription() . "\n";
            }
            $crontabContent .= $cronJob->getFullCronExpression() . "\n\n";
        }

        $response = new Response($crontabContent);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="crontab.txt"');

        return $response;
    }

    /**
     * Get the cron user (usually www-data)
     */
    private function getCronUser(): string
    {
        // Try to get the web server user
        $user = get_current_user();
        
        // On most systems, the web server runs as www-data
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $processUser = posix_getpwuid(posix_geteuid());
            if ($processUser && isset($processUser['name'])) {
                $user = $processUser['name'];
            }
        }
        
        return $user;
    }

    /**
     * Get the cron root directory
     */
    private function getCronRootDirectory(): string
    {
        return $this->getParameter('kernel.project_dir');
    }
}

