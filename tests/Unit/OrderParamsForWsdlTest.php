<?php

namespace App\Tests\Unit;

use Damis\ExperimentBundle\Command\ExecuteExperimentCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * SoapClient sends the parameters by position, so ExecuteExperimentCommand puts them in the order of the
 * operation in the WSDL the application ships (public/damis.wsdl, same operations as the DamisService).
 */
class OrderParamsForWsdlTest extends TestCase
{
    private BufferedOutput $output;

    protected function setUp(): void
    {
        $this->output = new BufferedOutput();
    }

    public function testParametersAreSortedInWsdlOrder(): void
    {
        $params = ['p' => 1, 'maxCalcTime' => 10, 'X' => 'http://x/data.arff', 'qty' => 0.8, 'maxIteration' => 100,
            'h2pNo' => 0, 'kFoldValidation' => false, 'h1pNo' => 5];

        $ordered = $this->order('MLP', $params);

        self::assertSame(['X', 'h1pNo', 'h2pNo', 'qty', 'kFoldValidation', 'maxIteration', 'p', 'maxCalcTime'], array_keys($ordered));
        self::assertSame($params['X'], $ordered['X']);
        self::assertSame('', $this->output->fetch());
    }

    public function testUnknownParameterIsDropped(): void
    {
        $ordered = $this->order('PCA', ['d' => 2, 'X' => 'x', 'maxCalcTime' => 10, 'projType' => true, 'extra' => 1]);

        self::assertSame(['X', 'projType', 'd', 'maxCalcTime'], array_keys($ordered));
        self::assertStringContainsString('not in the WSDL of PCA, not sent: extra', $this->output->fetch());
    }

    public function testMissingParameterKeepsParametersAsTheyAre(): void
    {
        $params = ['d' => 2, 'X' => 'x'];

        self::assertSame($params, $this->order('PCA', $params));
        self::assertStringContainsString('no value for WSDL parameter(s) projType, maxCalcTime of PCA', $this->output->fetch());
    }

    public function testUnknownOperationKeepsParametersAsTheyAre(): void
    {
        $params = ['b' => 1, 'a' => 2];

        self::assertSame($params, $this->order('NOPE', $params));
        self::assertStringContainsString('operation NOPE not found in the WSDL', $this->output->fetch());
    }

    private function order(string $function, array $params): array
    {
        $client = new \SoapClient(dirname(__DIR__, 2).'/public/damis.wsdl');
        $command = (new \ReflectionClass(ExecuteExperimentCommand::class))->newInstanceWithoutConstructor();

        return (new \ReflectionMethod($command, 'orderParamsForWsdl'))->invoke($command, $client, $function, $params, $this->output);
    }
}
