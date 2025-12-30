<?php

namespace Base\ConvertBundle\Controller;

use Damis\DatasetsBundle\Entity\Dataset;
use Base\ConvertBundle\Helpers\ReadFile;
use PHPExcel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use PHPExcel_IOFactory;
use PHPExcel_Writer_Excel2007;
use Doctrine\Persistence\ManagerRegistry;

class ConvertController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    /**
     * A helper function to get the full, absolute path to a dataset file.
     *
     * @param Dataset $entity
     * @return string|null
     */
    private function getFullFilePath($entity)
    {
        if (!$entity || !$entity->getFilePath()) {
            return null;
        }
        $projectRoot = $this->getParameter('kernel.project_dir');
        return $projectRoot . '/web' . $entity->getFilePath();
    }

    /**
     * Converts csv/txt/tab/xls/xlsx types to arff
     * and downloads it
     *
     * @param int $id
     * @return Response response
     *
     * @Route("{id}/convert/arff.html", name="convert_arff")
     */
    public function convertToArff($id)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['user' => $user, 'datasetId' => $id]);

        $fullFilePath = $this->getFullFilePath($entity);

        if ($fullFilePath && file_exists($fullFilePath)) {
            $format = pathinfo($fullFilePath, PATHINFO_EXTENSION);
            $filename = $entity->getDatasetTitle();
            $fileReader = new ReadFile();
            $rows = [];

            if ($format == 'arff') {
                $content = file_get_contents($fullFilePath);
                $contentLines = explode("\n", $content);
                if (isset($contentLines[0]) && stripos($contentLines[0], '@relation') === 0) {
                    $contentLines[0] = '@relation ' . preg_replace('/\s+/', '_', (string) $filename);
                }
                $fileContent = implode("\n", $contentLines);

                $response = new Response($fileContent);
                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.arff"', $filename));
                return $response;

            } elseif (in_array($format, ['txt', 'tab', 'csv'])) {
                $rows = $fileReader->getRows($fullFilePath, $format);
            } elseif (in_array($format, ['xls', 'xlsx'])) {
                $objPHPExcel = PHPExcel_IOFactory::load($fullFilePath);
                $rows = $objPHPExcel->getActiveSheet()->toArray();
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirectToRoute('datasets_list');
            }

            if ($rows === false) {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                return $this->redirectToRoute('datasets_list');
            }

            $hasHeaders = false;
            if (!empty($rows)) {
                foreach (reset($rows) as $header) {
                    if (!is_numeric($header)) {
                        $hasHeaders = true;
                        break;
                    }
                }
            }

            $arff = '@relation ' . preg_replace('/\s+/', '_', (string) $filename) . PHP_EOL;
            $headerRow = $hasHeaders ? array_values(array_shift($rows)) : array_keys(reset($rows));
            $firstDataRow = reset($rows);

            foreach ($headerRow as $key => $header) {
                $attributeName = $hasHeaders ? preg_replace('/[^\w\d_]/', '_', (string) $header) : 'attribute_' . $key;
                $sampleValue = $firstDataRow[$key] ?? null;
                $type = 'string';
                if (is_numeric($sampleValue)) {
                    $type = (!str_contains((string)$sampleValue, '.')) ? 'integer' : 'real';
                }
                $arff .= '@attribute ' . $attributeName . ' ' . $type . PHP_EOL;
            }

            $arff .= '@data' . PHP_EOL;
            foreach ($rows as $row) {
                $arff .= implode(',', array_values($row)) . PHP_EOL;
            }

            $response = new Response($arff);
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.arff"', $filename));
            return $response;
        }
        return $this->redirectToRoute('datasets_list');
    }

    /**
     * Converts arff type to a delimited format (txt, tab, csv)
     * and downloads it
     *
     * @param int $id
     * @param string $delimiter
     * @param string $extension
     * @return Response
     */
    private function convertFromArffAction($id, $delimiter, $extension)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['user' => $user, 'datasetId' => $id]);
        $fullFilePath = $this->getFullFilePath($entity);

        if ($fullFilePath && file_exists($fullFilePath)) {
            $filename = $entity->getDatasetTitle();
            $fileReader = new ReadFile();
            $rows = $fileReader->getRows($fullFilePath, 'arff');

            if ($rows === false) {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                return $this->redirectToRoute('datasets_list');
            }

            $output = '';
            $headers = [];
            $dataStarted = false;

            foreach ($rows as $row) {
                $line = $row[0];
                if ($dataStarted) {
                    $output .= str_replace(',', $delimiter, $line) . PHP_EOL;
                } elseif (stripos((string) $line, '@attribute') === 0) {
                    $parts = preg_split('/\s+/', (string) $line, 3);
                    $headers[] = $parts[1];
                } elseif (stripos((string) $line, '@data') === 0) {
                    $output .= implode($delimiter, $headers) . PHP_EOL;
                    $dataStarted = true;
                }
            }

            $response = new Response($output);
            $response->headers->set('Content-Type', 'text/plain; charset=utf-8');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.%s"', $filename, $extension));
            return $response;
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirectToRoute('datasets_list');
    }

    /**
     * @Route("{id}/convert/txt.html", name="convert_txt")
     */
    public function convertToTxt($id)
    {
        return $this->convertFromArffAction($id, ',', 'txt');
    }

    /**
     * @Route("{id}/convert/tab.html", name="convert_tab")
     */
    public function convertToTab($id)
    {
        return $this->convertFromArffAction($id, "\t", 'tab');
    }

    /**
     * @Route("{id}/convert/csv.html", name="convert_csv")
     */
    public function convertToCsv($id)
    {
        return $this->convertFromArffAction($id, ';', 'csv');
    }

    /**
     * Converts arff type to xlsx
     * and downloads it
     *
     * @param int $id
     * @param int $midas
     * @return Response
     *
     * @Route("{id}/convert/xls.html", name="convert_xls")
     */
    public function convertToXls($id, $midas = 0)
    {
        $user = $this->getUser();
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository(Dataset::class)->findOneBy(['user' => $user, 'datasetId' => $id]);
        $fullFilePath = $this->getFullFilePath($entity);

        if ($fullFilePath && file_exists($fullFilePath)) {
            $filename = $entity->getDatasetTitle();
            $fileReader = new ReadFile();
            $rows = $fileReader->getRows($fullFilePath, 'arff');

            if ($rows === false) {
                $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('Exceeded memory limit!', [], 'DatasetsBundle'));
                return $this->redirectToRoute('datasets_list');
            }

            $objPHPExcel = new PHPExcel();
            $sheet = $objPHPExcel->setActiveSheetIndex(0);
            $headers = [];
            $dataRows = [];
            $dataStarted = false;

            foreach ($rows as $row) {
                $line = $row[0];
                if ($dataStarted) {
                    $dataRows[] = explode(',', (string) $line);
                } elseif (stripos((string) $line, '@attribute') === 0) {
                    $parts = preg_split('/\s+/', (string) $line, 3);
                    $headers[] = $parts[1];
                } elseif (stripos((string) $line, '@data') === 0) {
                    $dataStarted = true;
                }
            }

            $sheet->fromArray($headers, null, 'A1');
            $sheet->fromArray($dataRows, null, 'A2');

            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

            if ($midas == 1) {
                $tempFile = $this->getParameter("kernel.cache_dir") . '/' . time() . $id . '.xlsx';
                $objWriter->save($tempFile);
                return new Response($tempFile);
            }

            $response = new Response();
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.xlsx"', $filename));

            ob_start();
            $objWriter->save('php://output');
            $response->setContent(ob_get_clean());

            return $response;
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirectToRoute('datasets_list');
    }
}