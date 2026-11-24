<?php

namespace App\Tests\Functional;

use Base\UserBundle\Entity\User;
use Damis\DatasetsBundle\Entity\Dataset;
use Damis\ExperimentBundle\Entity\Experiment;
use Damis\ExperimentBundle\Entity\Experimentstatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests against the test database (<database>_test, recreated by tests/bootstrap.php). Every test
 * creates the users and datasets it needs with unique names, so tests do not depend on each other.
 */
abstract class DamisWebTestCase extends WebTestCase
{
    /** a local file component (no service host) and its output parameter, the shape DatasetVoter looks for */
    protected const FILE_COMPONENT_ID = 3;
    protected const FILE_OUTPUT_PARAMETER_ID = 71;

    protected KernelBrowser $client;

    /** @var list<string> files written to public/uploads/tests by createDataset() */
    private array $files = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function createUser(string $prefix, string $password = 'Test-password-1', array $roles = ['ROLE_CONFIRMED']): User
    {
        $manager = static::getContainer()->get('fos_user.user_manager');
        $name = $prefix.'_'.bin2hex(random_bytes(4));

        /** @var User $user */
        $user = $manager->createUser();
        $user->setUsername($name);
        $user->setEmail($name.'@example.test');
        $user->setPlainPassword($password);
        $user->setEnabled(true);
        $user->setRoles($roles);
        $manager->updateUser($user);

        return $user;
    }

    protected function createAdmin(string $prefix = 'admin'): User
    {
        return $this->createUser($prefix, 'Test-password-1', ['ROLE_CONFIRMED', 'ROLE_ADMIN']);
    }

    /** Logs in through the real login form, with the CSRF token of the form unless $withCsrfToken is false. */
    protected function loginWithForm(string $username, string $password, bool $withCsrfToken = true): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = [
            '_username' => $username,
            '_password' => $password,
        ];
        if ($withCsrfToken) {
            $form['_csrf_token'] = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        }
        $this->client->request('POST', '/login_check', $form);
    }

    /** Dataset owned by $owner whose ARFF file exists in public/uploads/tests. */
    protected function createDataset(User $owner, string $title = 'test dataset'): Dataset
    {
        $dir = static::getContainer()->getParameter('kernel.project_dir').'/public/uploads/tests';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $relative = '/uploads/tests/'.bin2hex(random_bytes(6)).'.arff';
        file_put_contents($dir.'/'.basename($relative), self::irisArff());
        $this->files[] = $dir.'/'.basename($relative);

        $dataset = new Dataset();
        // a reference: $owner may come from the entity manager of a kernel the client has rebooted since
        $dataset->setUser($this->em()->getReference(User::class, $owner->getId()));
        $dataset->setDatasetTitle($title);
        $dataset->setDatasetCreated(time());
        $dataset->setDatasetIsMidas(false);
        $dataset->setHidden(false);
        $dataset->setFilePath($relative);
        $this->em()->persist($dataset);
        $this->em()->flush();

        return $dataset;
    }

    /**
     * Experiment whose workflow uses $dataset as the input file (EXISTING FILE box), like an example copied
     * with experiment_example_copy.
     */
    protected function createExperiment(User $owner, Dataset $input, string $status): Experiment
    {
        $this->ensureReferenceData();
        $boxes = ['b1' => [
            'componentId' => self::FILE_COMPONENT_ID,
            'boxId' => 'b1',
            'form_parameters' => [['id' => self::FILE_OUTPUT_PARAMETER_ID, 'value' => (string) $input->getDatasetId()]],
        ]];

        $experiment = new Experiment();
        $experiment->setName('test experiment');
        $experiment->setUser($this->em()->getReference(User::class, $owner->getId()));
        $experiment->setStatus($this->em()->getRepository(Experimentstatus::class)->findOneBy(['experimentstatus' => $status]));
        $experiment->setGuiData(json_encode($boxes).'***[]');
        $this->em()->persist($experiment);
        $this->em()->flush();

        return $experiment;
    }

    /** The reference rows of sql/data.sql that the tested code relies on (statuses, the uploaded file component). */
    protected function ensureReferenceData(): void
    {
        $connection = $this->em()->getConnection();
        foreach (['SAVED' => 1, 'EXECUTING' => 2, 'FINISHED' => 3, 'ERROR' => 4, 'SUSPENDED' => 5, 'EXAMPLE' => 6] as $name => $id) {
            $connection->executeStatement('INSERT IGNORE INTO experimentstatus (ExperimentStatusID, ExperimentStatus) VALUES (?, ?)', [$id, $name]);
        }
        foreach ([1 => 'INPUT_CONNECTION', 2 => 'OUTPUT_CONNECTION', 3 => 'INPUT_VALUE'] as $id => $name) {
            $connection->executeStatement('INSERT IGNORE INTO parameterconnectiontype (ParameterConnectionTypeID, ParameterConnectionType) VALUES (?, ?)', [$id, $name]);
        }
        $connection->executeStatement("INSERT IGNORE INTO cluster (ClusterID, ClusterName, ClusterWorkloadHost) VALUES (1, 'Server', 'test')");
        // as component 3 of sql/data.sql
        $connection->executeStatement(
            'INSERT IGNORE INTO component (ComponentID, ComponentName, ComponentIcon, ComponentWSDLRunHost, ComponentWSDLCallFunction, FormType, ClusterID) VALUES (?, ?, ?, NULL, ?, ?, 1)',
            [self::FILE_COMPONENT_ID, 'Choose uploaded file', 'existing-file-ico.jpeg', 'EXISTING FILE', 'UploadedFile']
        );
        $connection->executeStatement(
            'INSERT IGNORE INTO parameter (ParameterID, ParameterName, ParameterIsRequired, ParameterSlug, ParameterConnectionTypeID, ComponentID) VALUES (?, ?, 1, ?, 2, ?)',
            [self::FILE_OUTPUT_PARAMETER_ID, 'Y', 'Y', self::FILE_COMPONENT_ID]
        );
    }

    protected static function irisArff(): string
    {
        return "@RELATION iris\n@ATTRIBUTE sepallength REAL\n@ATTRIBUTE sepalwidth REAL\n@ATTRIBUTE class {A,B}\n@DATA\n"
            ."5.1,3.5,A\n4.9,3.0,A\n7.0,3.2,B\n6.4,3.2,B\n";
    }
}
