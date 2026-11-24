<?php

namespace App\Tests\Functional;

use Damis\DatasetsBundle\Entity\Dataset;

/** Datasets are readable by id only by their owner, administrators, and anybody for inputs of examples. */
class DatasetAccessTest extends DamisWebTestCase
{
    public function testOwnerReadsDataset(): void
    {
        $owner = $this->createUser('owner');
        $dataset = $this->createDataset($owner);

        $this->client->loginUser($owner);

        $this->assertReadable($dataset);
    }

    public function testOtherUserDoesNotReadDataset(): void
    {
        $dataset = $this->createDataset($this->createUser('owner'));

        $this->client->loginUser($this->createUser('other'));

        $this->assertNotReadable($dataset);
    }

    public function testAdministratorReadsDataset(): void
    {
        $dataset = $this->createDataset($this->createUser('owner'));

        $this->client->loginUser($this->createAdmin());

        $this->assertReadable($dataset);
    }

    public function testOtherUserReadsInputOfExample(): void
    {
        $author = $this->createUser('author');
        $dataset = $this->createDataset($author);
        $this->createExperiment($author, $dataset, 'EXAMPLE');

        $this->client->loginUser($this->createUser('other'));

        $this->assertReadable($dataset);
    }

    public function testOtherUserDoesNotReadInputOfOrdinaryExperiment(): void
    {
        $author = $this->createUser('author');
        $dataset = $this->createDataset($author);
        $this->createExperiment($author, $dataset, 'FINISHED');

        $this->client->loginUser($this->createUser('other'));

        $this->assertNotReadable($dataset);
    }

    private function assertReadable(Dataset $dataset): void
    {
        $this->client->request('GET', '/datasets/'.$dataset->getDatasetId().'/convert_csv');
        self::assertResponseIsSuccessful();
        self::assertStringStartsWith("sepallength,sepalwidth,class\n5.1,3.5,A\n", $this->client->getInternalResponse()->getContent());

        $this->client->request('GET', '/experiment/chart/dataset.html', ['dataset_url' => $dataset->getDatasetId()]);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Dataset not found', $this->client->getInternalResponse()->getContent());
    }

    private function assertNotReadable(Dataset $dataset): void
    {
        $this->client->request('GET', '/datasets/'.$dataset->getDatasetId().'/convert_csv');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/experiment/chart/dataset.html', ['dataset_url' => $dataset->getDatasetId()]);
        self::assertStringContainsString('Dataset not found', $this->client->getInternalResponse()->getContent());
    }
}
