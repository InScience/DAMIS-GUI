<?php

namespace Damis\DatasetsBundle\Security;

use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\ExperimentBundle\Entity\Experimentstatus;
use Damis\ExperimentBundle\Entity\Parameter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who may read a dataset by its id (charts, matrix view, technical details, component forms, downloads):
 * - its owner,
 * - an administrator,
 * - anybody logged in, if the dataset is an input file of an experiment example: examples are public, and
 *   copying one keeps the author's input dataset ids in the copied workflow.
 *
 * Before this check any logged-in user could read every dataset by guessing its id.
 */
final class DatasetVoter extends Voter
{
    public const VIEW = 'DATASET_VIEW';

    /** @var array<int, true>|null ids of datasets used as input files of example experiments (built once per request) */
    private ?array $exampleDatasetIds = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Dataset;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Dataset $subject */
        $owner = $subject->getUser();
        if ($owner && $owner->getUserIdentifier() === $user->getUserIdentifier()) {
            return true;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        return isset($this->getExampleDatasetIds()[(int) $subject->getDatasetId()]);
    }

    /**
     * Dataset ids stored in the workflows of EXAMPLE experiments as values of the output parameters of the
     * local file components (existing file, uploaded file, MIDAS file - components without a service host).
     *
     * @return array<int, true>
     */
    private function getExampleDatasetIds(): array
    {
        if (null !== $this->exampleDatasetIds) {
            return $this->exampleDatasetIds;
        }
        $this->exampleDatasetIds = [];

        $status = $this->em->getRepository(Experimentstatus::class)->findOneBy(['experimentstatus' => 'EXAMPLE']);
        if (!$status) {
            return $this->exampleDatasetIds;
        }

        // output parameters (connection type 2) of components that run locally
        $fileOutputParameters = [];
        foreach ($this->em->getRepository(Parameter::class)->findAll() as $parameter) {
            $component = $parameter->getComponent();
            if ($component && !$component->getWsdlRunHost()
                && $parameter->getConnectionType() && 2 == $parameter->getConnectionType()->getId()) {
                $fileOutputParameters[(int) $parameter->getId()] = true;
            }
        }

        foreach ($this->em->getRepository(Experiment::class)->findBy(['status' => $status]) as $experiment) {
            // GUI data: boxes JSON *** connections JSON
            $boxes = json_decode(html_entity_decode(explode('***', (string) $experiment->getGuiData())[0]), true);
            if (!is_array($boxes)) {
                continue;
            }
            foreach ($boxes as $box) {
                foreach ((array) ($box['form_parameters'] ?? []) as $formParameter) {
                    $id = (int) ($formParameter['id'] ?? 0);
                    $value = $formParameter['value'] ?? null;
                    if (isset($fileOutputParameters[$id]) && is_numeric($value)) {
                        $this->exampleDatasetIds[(int) $value] = true;
                    }
                }
            }
        }

        return $this->exampleDatasetIds;
    }
}
