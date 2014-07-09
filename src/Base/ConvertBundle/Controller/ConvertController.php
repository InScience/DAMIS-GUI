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
     * and downloads it
     *
     * @param String $id
     * @return Response response
     *
     * @Route("{id}/convert/arff.html", name="convert_arff")
     * @Template()
     */
    public function convertToArffAction($id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity && $entity->getFilePath()){
            $format = explode('.', $entity->getFilePath());
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'arff'){
                $content = file_get_contents('.' . $entity->getFilePath());
                $content = explode("\n",$content);
                reset($content);
                $firstKey = key($content);
                if(strpos(strtolower($content[$firstKey]), '@relation') === 0)
                    $content[$firstKey] = '@relation ' . $filename;
                else {
                    $content[$firstKey] = '@relation ' . $filename . "\n" . $content[$firstKey];
                }
                $fileContent = '';
                foreach($content as $row){
                    $fileContent .= $row . "\n";
                }
                $response = new Response($fileContent);
                $response->headers->set('Content-Type', 'application/arff; charset=utf-8');
                $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.arff"', $filename));

                return $response;

            }
            elseif($format == 'txt' || $format == 'tab' || $format == 'csv'){
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFile()['fileName'] , $format);
            } elseif($format == 'xls' || $format == 'xlsx'){
                $objPHPExcel = PHPExcel_IOFactory::load('.' . $entity->getFile()['fileName']);
                $rows = $objPHPExcel->setActiveSheetIndex(0)->toArray();
                array_unshift($rows, null);
                unset($rows[0]);
            } else{
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirect($this->generateUrl('datasets_list'));
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
        return $this->redirect($this->generateUrl('datasets_list'));
    }

    /**
     * Converts arff type to txt
     * and downloads it
     *
     * @param String $id
     * @return Response response
     *
     * @Route("{id}/convert/txt.html", name="convert_txt")
     * @Template()
     */
    public function convertToTxtAction($id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity && $entity->getFilePath()){
            $format = explode('.', $entity->getFilePath());
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'arff'){
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' .$entity->getFilePath(), 'arff');
                foreach($rows as $key => $row){
                    if(strtolower($row[0]) != '@data'){
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
            }else {
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirect($this->generateUrl('datasets_list'));
            }
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirect($this->generateUrl('datasets_list'));
    }
    /**
     * Converts arff type to txt
     * and downloads it
     *
     * @param String $id
     * @return Response response
     *
     * @Route("{id}/convert/tab.html", name="convert_tab")
     * @Template()
     */
    public function convertToTabAction($id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity && $entity->getFilePath()){
            $format = explode('.', $entity->getFilePath());
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'arff'){
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFilePath(), 'arff');
                foreach($rows as $key => $row){
                    if(strtolower($row[0]) != '@data'){
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
            } else{
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirect($this->generateUrl('datasets_list'));
            }
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirect($this->generateUrl('datasets_list'));
    }
    /**
     * Converts arff type to csv
     * and downloads it
     *
     * @param String $id
     * @return Response response
     *
     * @Route("{id}/convert/csv.html", name="convert_csv")
     * @Template()
     */
    public function convertToCsvAction($id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity && $entity->getFilePath()){
            $format = explode('.', $entity->getFilePath());
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'arff'){
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFilePath(), 'arff');
                $file = '';
                $first = true;
                foreach($rows as $key => $row){
                    if(strpos(strtolower($row[0]), '@attribute') === 0){
                        $header = explode(' ', $row[0]);
                        if(!$first)
                            $file .= ';';
                        else
                            $first = false;
                        if(!isset($header[1]))
                            $header[1] = '';
                        $file .= $header[1];
                    }
                    if(strtolower($row[0]) != '@data'){
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
                            $file .= ';' . $value;
                        else
                            $file .= $value;
                    $file .= PHP_EOL;
                }
                $response = new Response($file);
                $response->headers->set('Content-Type', 'application/csv; charset=utf-8');
                $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s.csv"', $filename));

                return $response;
            } else{
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirect($this->generateUrl('datasets_list'));
            }
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirect($this->generateUrl('datasets_list'));
    }
    /**
     * Converts arff type to xsl
     * and downloads it
     *
     * @param String $id
     * @return Response response
     *
     * @Route("{id}/convert/xls.html", name="convert_xls")
     * @Template()
     */
    public function convertToXlsAction($id)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('DamisDatasetsBundle:Dataset')
            ->findOneBy(array('userId' => $user, 'datasetId' => $id));
        if($entity && $entity->getFilePath()){
            $format = explode('.', $entity->getFilePath());
            $format = $format[count($format)-1];
            $filename = $entity->getDatasetTitle();
            if ($format == 'arff'){
                $fileReader = new ReadFile();
                $rows = $fileReader->getRows('.' . $entity->getFilePath(), 'arff');
                $objPHPExcel = new PHPExcel();
                $objPHPExcel->setActiveSheetIndex(0);
                $colCount = 0;
                foreach($rows as $key => $row){
                    if(strpos(strtolower($row[0]), '@attribute') === 0){
                        $header = explode(' ', $row[0]);
                        if(!isset($header[1]))
                            $header[1] = '';
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colCount , 1, $header[1]);
                        $colCount++;
                    }
                    if(strtolower($row[0]) != '@data'){
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

                header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;' . sprintf('filename="%s.xlsx"', $filename));

                $objWriter->save('php://output');

                return new Response();
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Dataset has wrong format!');
                return $this->redirect($this->generateUrl('datasets_list'));
            }
        }
        $this->get('session')->getFlashBag()->add('error', 'Error!');
        return $this->redirect($this->generateUrl('datasets_list'));
    }
}
