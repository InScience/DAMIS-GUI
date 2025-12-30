<?php

namespace Damis\AlgorithmBundle\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Damis\AlgorithmBundle\Entity\File;
use Damis\AlgorithmBundle\Form\Type\FileType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Algorithms controller.
 */
#[Route('/algorithm')]
class AlgorithmController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ManagerRegistry $doctrine
    ) {}

    /**
     * User algorithms list window
     */
    #[Route('/list.html', name: 'algorithm_list')]
    public function list(Request $request, PaginatorInterface $paginator)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();

        $sort = $request->query->get('order_by');
        $order = [];
        if ($sort == 'titleASC') {
            $order = ['fileTitle' => 'ASC'];
        } elseif ($sort == 'titleDESC') {
            $order = ['fileTitle' => 'DESC'];
        } elseif ($sort == 'createdASC') {
            $order = ['fileCreated' => 'ASC'];
        } elseif ($sort == 'createdDESC') {
            $order = ['fileCreated' => 'DESC'];
        }

        $entities = $em->getRepository(File::class)->findBy(['user' => $user], $order);

        $pagination = $paginator->paginate(
            $entities,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('@DamisAlgorithm/Algorithm/list.html.twig', [
            'entities' => $pagination,
        ]);
    }

    /**
     * Upload new algorithm form display
     */
    #[Route('/new.html', name: 'algorithm_new', methods: ['GET'])]
    public function new()
    {
        $entity = new File();
        $entity->setUser($this->getUser());
        $form = $this->createForm(FileType::class, $entity);

        return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   /**
     * Create new algorithm (handle form submission)
     */
    #[Route('/create.html', name: 'algorithm_create', methods: ['POST'])]
    public function create(Request $request, SessionInterface $session, TranslatorInterface $translator)
    {
        $entity = new File();
        $user = $this->getUser();
        $entity->setUser($user); 

       $form = $this->createForm(FileType::class, $entity, [
            'validation_groups' => ['Default', 'create'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager(); 

            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $form->get('file')->getData(); 

            if ($uploadedFile) {
                $userSubDir = $entity->getUserIdMd5File();
                if (!$userSubDir) {
                     $this->addFlash('error', 'Could not determine user directory.');
                     return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [
                        'form' => $form->createView(),
                     ]);
                }

                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/algorithms/' . $userSubDir;
                if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                    $this->addFlash('error', 'Failed to create upload directory.');
                    $this->logger->error(sprintf('Directory "%s" was not created', $uploadDir));
                    return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [ 'form' => $form->createView() ]);
                }

                $originalFilename = pathinfo((string) $uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                try {
                    $uploadedFile->move($uploadDir, $newFilename);
                    $entity->setFilePath('/uploads/algorithms/' . $userSubDir . '/' . $newFilename);
                    $entity->setFile(null);

                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                     $this->addFlash('error', 'Failed to upload file: ' . $e->getMessage());
                     $this->logger->error("File upload failed: " . $e->getMessage());
                     return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [
                         'form' => $form->createView(),
                     ]);
                }
            } else {
                 $this->addFlash('error', 'Algorithm file is required.');
                 return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [
                     'form' => $form->createView(),
                 ]);
            }

            $entity->setFileCreated(time());
            $em->persist($entity);
            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans('Algorithm successfully uploaded! Project administrators will connect with you for next actions', [], 'AlgorithmBundle'));
            return $this->redirectToRoute('algorithm_list');
        }

        return $this->render('@DamisAlgorithm/Algorithm/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit algorithm form display
     */
    #[Route('/{id}/edit.html', name: 'algorithm_edit', methods: ['GET'])]
    public function edit($id)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(File::class)->findOneBy(['fileId' => $id, 'user' => $user]);

        if (!$entity) {
            $this->logger->error('Invalid try to access algorithm id: '.$id.' by user id: '.$user->getId()); 
            $this->addFlash('error', 'Algorithm not found or access denied.');
            return $this->redirectToRoute('algorithm_list');
        }

        $form = $this->createForm(FileType::class, $entity, [
             'is_edit' => true,
        ]);

        return $this->render('@DamisAlgorithm/Algorithm/edit.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
        ]);
    }

    /**
     * Update algorithm file description (handle edit form submission)
     */
    #[Route('/{id}/update.html', name: 'algorithm_update', methods: ['POST'])]
    public function update(Request $request, SessionInterface $session, TranslatorInterface $translator, $id): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(File::class)->findOneBy(['fileId' => $id, 'user' => $user]);

        if (!$entity) {
            $this->logger->error('Update failed: Algorithm not found or access denied.', ['id' => $id, 'user_id' => $user->getId()]);
            $this->addFlash('error', 'Algorithm not found or access denied.');
            return $this->redirectToRoute('algorithm_list');
        }

        $this->logger->info('--- PRE-FORM DATA ---', [
            'id' => $entity->getFileId(),
            'original_title' => $entity->getFileTitle(),
            'original_description' => $entity->getFileDescription()
        ]);

        $form = $this->createForm(FileType::class, $entity, [
            'is_edit' => true,
            'validation_groups' => ['Default'],
        ]);

        $form->handleRequest($request);

        $this->logger->info('--- POST-FORM DATA ---', [
            'id' => $entity->getFileId(),
            'title_after_handleRequest' => $entity->getFileTitle(),
            'description_after_handleRequest' => $entity->getFileDescription(),
            'is_submitted' => $form->isSubmitted(),
            'is_valid' => $form->isValid()
        ]);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Form is SUBMITTED and VALID. Preparing to flush.');

            $entity->setFileUpdated(time());

            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();
            $changeset = $uow->getEntityChangeSet($entity);

            $this->logger->info('--- DOCTRINE CHANGESET ---', [
                'id' => $entity->getFileId(),
                'changes' => $changeset
            ]);

            if (empty($changeset)) {
                $this->logger->warning('Doctrine detected NO CHANGES to the entity. Flush will do nothing.');
            }

            $em->flush();

            $session->getFlashBag()->add('success', $translator->trans('Algorithm file successfully updated!', [], 'AlgorithmBundle'));
            return $this->redirectToRoute('algorithm_list');
        }

        $this->logger->error('Form is INVALID or NOT SUBMITTED.', [
            'is_submitted' => $form->isSubmitted(),
            'is_valid' => $form->isValid(),
            'errors' => (string) $form->getErrors(true, false)
        ]);

        return $this->render('@DamisAlgorithm/Algorithm/edit.html.twig', [
            'form' => $form->createView(),
            'entity' => $entity,
        ]);
    }

    /**
     * Delete algorithms
     */
    #[Route('/delete.html', name: 'algorithm_delete', methods: ['POST'])]
    public function delete(Request $request, SessionInterface $session, TranslatorInterface $translator)
    {
        $user = $this->getUser();
        $filesJson = $request->request->get('file-delete-list');
        $files = json_decode($filesJson);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($files)) {
             $this->addFlash('error', 'Invalid request data for deletion.');
             return $this->redirectToRoute('algorithm_list');
        }

        $em = $this->doctrine->getManager();
        $deletedCount = 0;
        $projectRoot = $this->getParameter('kernel.project_dir');
        $publicDir = $projectRoot . '/public';

        foreach ($files as $id) {
             if (!is_numeric($id)) continue;

            /* @var File|null $file */
            $file = $em->getRepository(File::class)->findOneBy(['fileId' => $id, 'user' => $user]);
            if ($file) {
                $filePath = $file->getFilePath();
                if ($filePath) {
                    $fullFilePath = $publicDir . $filePath;
                    if (file_exists($fullFilePath) && is_file($fullFilePath)) {
                        if (@unlink($fullFilePath)) {
                             $this->logger->info("Deleted algorithm file: {$fullFilePath}");
                        } else {
                             $this->logger->error("Could not delete algorithm file: {$fullFilePath}");
                             $session->getFlashBag()->add('error', $translator->trans('Could not delete file for algorithm ID %id%.', ['%id%' => $id], 'AlgorithmBundle'));
                        }
                    } else {
                         $this->logger->warning("Algorithm file not found or is not a file: {$fullFilePath}");
                    }
                }
                $em->remove($file);
                $deletedCount++;
            } else {
                 $this->logger->warning("User {$user->getId()} tried to delete non-existent or non-owned algorithm ID: {$id}");
            }
        }

        if ($deletedCount > 0) {
            $em->flush();
            $session->getFlashBag()->add('success', $translator->trans('Successfully deleted %count% algorithm(s).', ['%count%' => $deletedCount], 'AlgorithmBundle'));
        } else {
             $session->getFlashBag()->add('warning', $translator->trans('No algorithms selected or found for deletion.', [], 'AlgorithmBundle'));
        }


        return $this->redirectToRoute('algorithm_list');
    }
}