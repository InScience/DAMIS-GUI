<?php

namespace Damis\ExperimentBundle\Controller;

use Damis\ExperimentBundle\Entity\Experiment;
use Damis\DatasetsBundle\Controller\MidasController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;


class ExperimentHistoryController extends AbstractController
{

    /**
     * Inject services via the constructor
     */
    public function __construct(
        private readonly MidasController $midasService,
        private readonly ManagerRegistry $doctrine,
        private readonly PaginatorInterface $paginator
    ) {
    }

    /**
     * Lists all User entities.
     */
    #[Route("experiments.html", name: "experiments_history", methods: ["GET"])]
    public function index(Request $request)
    {
        $this->midasService->checkSession();
        
        $em = $this->doctrine->getManager();
        
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to view experiment history.');
        }
        $entities = $em->getRepository(Experiment::class)->getUserExperiments($user);

        $pagination = $this->paginator->paginate(
            $entities,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('@DamisExperiment/ExperimentHistory/index.html.twig', [
            'entities' => $pagination
        ]);
    }

    /**
     * Lists all User entities.
     */
    #[Route("experiments_examples.html", name: "experiments_examples", methods: ["GET"])]
    public function examples(Request $request)
    {
        $this->midasService->checkSession();
        
        $em = $this->doctrine->getManager();
        
        $entities = $em->getRepository(Experiment::class)->getExperimentsExamples();

        $pagination = $this->paginator->paginate(
            $entities,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('@DamisExperiment/ExperimentHistory/examples.html.twig', [
            'entities' => $pagination
        ]);
    }
}