<?php
/**
 * Created by PhpStorm.
 * User: Deividas
 * Date: 14.3.12
 * Time: 10.34
 */

namespace Base\ConvertBundle\Helpers;


use Damis\DatasetsBundle\Entity\Dataset;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

class ReadFile {

    /**
     * Reads file and returns rows
     *
     * @param String $path
     * @param String $format
     * @return array
     */
    function getRows($path, $format) {
        $row = 0;
        $rows = array();
        $memoryLimit = ini_get('memory_limit');
        $suffix = '';
        sscanf ($memoryLimit, '%u%c', $number, $suffix);
        if (isset ($suffix))
        {
            $number = $number * pow (1024, strpos (' KMG', $suffix));
        }
        if(memory_get_usage(true) + filesize($path) * 5.8 > $number)
            return false;
        if($format == 'tab')
            $delimiter = "\t";
        elseif ($format == 'arff') 
            $delimiter = ",";
        else{
            $delimiters = array(
                'comma'     => ",",
                'semicolon' => ";",
                'space'     => " ",
                'tab'       => "\t"
            );
            $content = file_get_contents($path);
            $content = explode("\n", $content);
            // Find most often delimeter
            foreach ($delimiters as $key => $delim) {
                $res[$key] = substr_count(trim($content[(int)floor(count($content)/ 2)]), $delim);
            }
            arsort($res);

            reset($res);
            $first_key = key($res);

            $delimiter = $delimiters[$first_key];
        }

        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, null, $delimiter)) !== FALSE) {
                if(memory_get_usage(true) > $number - 1000000){
                    return false;
                }

                $num = count($data);
                $row++;
                for ($c = 0; $c < $num; $c++) {
                    if (!empty($data[$c]))
                        $rows[$row][] = trim($data[$c]);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Returns files attributes
     *
     * @param String $path
     * @param boolean $withType True - returning associative array with type and attribute name
     * @return array
     */
    function getAttributes($path, $withType = false) {
        $rows = $this->getRows($path, 'arff');
        $attributes = array();
        foreach($rows as $row){
            if(strpos(strtolower($row[key($row)]), '@attribute') === 0){
                $attr = explode(' ', $row[key($row)]);
                if(!$withType)
                    $attributes[] = $attr[1];
                else
                    $attributes[] = array('type' => strtolower($attr[2]), 'name' => $attr[1]);
            }
        }

        return $attributes;
    }

    /**
     * Returns file attributes with class attribute
     *
     * @param String $path
      * @return array
     */
    function getClassAttr($path) {
        $rows = $this->getRows($path, 'arff');
        $attributes = [];
        foreach($rows as $row){
            if(strpos(strtolower($row[key($row)]), '@attribute class') === 0){
                $attr = explode(' ', $row[key($row)]);
                $attributes[] = $attr[1]. '_attr';
            } else if(strpos(strtolower($row[key($row)]), '@attribute') === 0){
                $attr = explode(' ', $row[key($row)]);
                    $attributes[] = $attr[1];
            }
        }
        return $attributes;
    }

    /**
     * Select dataset features
     *
     * @param $datasetId
     * @param $attr
     * @param $class
     * @param $userId
     * @param $container
     * @return int
     */
    public function selectFeatures($datasetId, $attr, $class, $userId, $container)
    {
        $em = $container->get('doctrine')->getEntityManager();
        $dataset = $em->getRepository('DamisDatasetsBundle:Dataset')->findOneByDatasetId($datasetId);
        $rows = @$this->getRows($container->get('kernel')->getRootDir() . '/../web' . $dataset->getFilePath(), 'arff');
        $nr = 0;
        $file = '';

        if(!in_array($class, $attr) && $class != ""){
            $attrs = $attr;
            $attrs[] = $class;
        } else
            $attrs = $attr;

        foreach($rows as $row){
           if(strpos(strtolower($row[0]), '@attribute') === 0){
                if($nr == $class && $class != ""){
                    $header = explode(' ', $row[0]);
                    if(strpos(strtolower($row[0]), '@attribute class') === 0)
                        $file .= '@attribute class ' . $header[2];
                    else
                        $file .= '@attribute class ' . $header[1];
                    $file .= PHP_EOL;
                }
                elseif(in_array($nr, $attr)){
                    foreach($row as $value)
                        $file .= $value;
                    $file .= PHP_EOL;
                }
                $nr++;
            } elseif(strpos(strtolower($row[0]), '@data') === 0 ||
                strpos(strtolower($row[0]), '@relation') === 0) {
                foreach($row as $value)
                    $file .= $value;
                $file .= PHP_EOL;
            } else {
                foreach($attrs as $key => $at){
                    if($key > 0)
                        $file .= ',' . $row[$at];
                    else
                        $file .= $row[$at];
                }
                $file .= PHP_EOL;
            }
        }
        $temp_folder = $container->getParameter("kernel.cache_dir");
        $temp_file = $temp_folder . '/' . basename($dataset->getDatasetTitle() . time() . '.arff');

        $filew = fopen($temp_file,"w");
        fwrite($filew, $file);
        fclose($filew);

        $file = new File($temp_file);
        $user = $em->getRepository('BaseUserBundle:User')->findOneById($userId);
        $file_entity = new Dataset();
        $file_entity->setUserId($user);
        $file_entity->setDatasetTitle('experiment result');
        $file_entity->setDatasetCreated(time());
        $file_entity->setDatasetIsMidas(false);
        $file_entity->setHidden(true);
        $em->persist($file_entity);
        $em->flush();

        $ref_class = new ReflectionClass('Damis\DatasetsBundle\Entity\Dataset');
        $mapping = $container->get('iphp.filestore.mapping.factory')->getMappingFromField($file_entity, $ref_class, 'file');
        $file_data = $container->get('iphp.filestore.filestorage.file_system')->upload($mapping, $file);
        $file_entity->setFile($file_data);
        $file_entity->setFilePath($file_data['path']);
        $em->flush();

        unlink($temp_file);

        return $file_entity->getDatasetId();

    }
}