<?php

namespace Base\ConvertBundle\Controller;

use Base\ConvertBundle\Helpers\ReadFile;
use PHPExcel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use PHPExcel_IOFactory;
use PHPExcel_Writer_Excel2007;

class ConvertController extends Controller
{
    /**
     * Converts csv/txt/tab/xls/xlsx types to arff
     *
     * @param String $filename
     * @param String $format
     * @return Response response
     *
     * @Route("{filename}/{format}/convert/arff.html")
     * @Template()
     */
    public function convertToArffAction($filename, $format)
    {
        if($format != 'xls' && $format != 'xlsx'){
            $fileReader = new ReadFile();
            $rows = $fileReader->getRows($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.' . $format, $format);
        } else{
            $objPHPExcel = PHPExcel_IOFactory::load($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.' . $format);
            $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
            array_unshift($rows, null);
            unset($rows[0]);
        }
       $hasHeaders = false;
        if(!empty($rows)){
            foreach($rows[1] as $header){
                 if(!(is_numeric($header))){
                     $hasHeaders = true;
                 }
            }
        }
        $arff = '';
        $arff .= '@relation ' . $filename . PHP_EOL;
        if($hasHeaders){
            foreach($rows[1] as $key => $header){
                if(is_int($rows[2][$key] + 0))
                    $arff .= '@attribute ' . $header . ' ' . 'integer' . PHP_EOL;
                else if(is_float($rows[2][$key] + 0))
                    $arff .= '@attribute ' . $header . ' ' . 'real' . PHP_EOL;

            }
        } else {
            foreach($rows[1] as $key => $header){
                if(is_int($rows[2][$key] + 0))
                    $arff .= '@attribute ' . 'attr' . $key . ' ' . 'integer' . PHP_EOL;
                else if(is_float($rows[2][$key] + 0))
                    $arff .= '@attribute ' . 'attr' . $key . ' ' . 'real' . PHP_EOL;

            }
        }
        $arff .= '@data' . PHP_EOL;
        if($hasHeaders)
            unset($rows[1]);
        foreach($rows as $row){
            foreach($row as $key => $value)
                if($key > 0)
                    $arff .= ',' . $value;
                else
                    $arff .= $value;
            $arff .= PHP_EOL;
        }
        $response = new Response($arff);
        $response->headers->set('Content-Type', 'application/arff; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.arff"', $filename));

        return $response;
    }

    /**
     * Converts arff type to txt
     *
     * @param String $filename
     * @return Response response
     *
     * @Route("{filename}/convert/txt.html")
     * @Template()
     */
    public function convertToTxtAction($filename)
    {
        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.arff', 'arff');
        foreach($rows as $key => $row){
            if($row[0] != '@data'){
                unset($rows[$key]);
            } else {
                unset($rows[$key]);
                break;
            }
        }
        $file = '';
        foreach($rows as $row){
            foreach($row as $key => $value)
                if($key > 0)
                    $file .= ',' . $value;
                else
                    $file .= $value;
            $file .= PHP_EOL;
        }
        $response = new Response($file);
        $response->headers->set('Content-Type', 'application/txt; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.txt"', $filename));

        return $response;
    }
    /**
     * Converts arff type to txt
     *
     * @param String $filename
     * @return Response response
     *
     * @Route("{filename}/convert/tab.html")
     * @Template()
     */
    public function convertToTabAction($filename)
    {
        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.arff', 'arff');
        foreach($rows as $key => $row){
            if($row[0] != '@data'){
                unset($rows[$key]);
            } else {
                unset($rows[$key]);
                break;
            }
        }
        $file = '';
        foreach($rows as $row){
            foreach($row as $key => $value)
                if($key > 0)
                    $file .= "\t" . $value;
                else
                    $file .= $value;
            $file .= PHP_EOL;
        }
        $response = new Response($file);
        $response->headers->set('Content-Type', 'application/tab; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.tab"', $filename));

        return $response;
    }
    /**
     * Converts arff type to csv
     *
     * @param String $filename
     * @return Response response
     *
     * @Route("{filename}/convert/csv.html")
     * @Template()
     */
    public function convertToCsvAction($filename)
    {
        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.arff', 'arff');
        $file = '';
        $first = true;
        foreach($rows as $key => $row){
            if(strpos($row[0], '@attribute') === 0){
                $header = explode(' ', $row[0]);
                if(!$first)
                    $file .= ',';
                else
                    $first = false;
                $file .= $header[1];
            }
            if($row[0] != '@data'){
                unset($rows[$key]);
            } else {
                $file .= PHP_EOL;
                unset($rows[$key]);
                break;
            }
        }
        foreach($rows as $row){
            foreach($row as $key => $value)
                if($key > 0)
                    $file .= ',' . $value;
                else
                    $file .= $value;
            $file .= PHP_EOL;
        }
        $response = new Response($file);
        $response->headers->set('Content-Type', 'application/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.csv"', $filename));

        return $response;
    }
    /**
     * Converts arff type to xsl
     *
     * @param String $filename
     * @return Response response
     *
     * @Route("{filename}/convert/xls.html")
     * @Template()
     */
    public function convertToXlsAction($filename)
    {
        $fileReader = new ReadFile();
        $rows = $fileReader->getRows($this->get('kernel')->getRootDir() . '/../web/' . $filename . '.arff', 'arff');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $colCount = 0;
        foreach($rows as $key => $row){
            if(strpos($row[0], '@attribute') === 0){
                $header = explode(' ', $row[0]);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colCount , 1, $header[1]);
                $colCount++;
            }
            if($row[0] != '@data'){
                unset($rows[$key]);
            } else {
                unset($rows[$key]);
                break;
            }
        }
        $rowCount = 2;
        $colCount = 0;
        foreach($rows as $row){
            foreach($row as $value){
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colCount, $rowCount, $value);
                $colCount++;
            }
            $colCount = 0;
            $rowCount++;
        }
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;' . sprintf('filename="%s.xlsx"', $filename));

        $objWriter->save('php://output');

        return new Response();
    }
}
