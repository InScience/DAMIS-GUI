<?php
/**
 * Created by PhpStorm.
 * User: Deividas
 * Date: 14.3.12
 * Time: 10.34
 */

namespace Base\ConvertBundle\Helpers;


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

        if($format == 'tab')
            $delimiter = "\t";
        else
            $delimiter = ',';

        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, null, $delimiter)) !== FALSE) {
                $num = count($data);
                $row++;
                for ($c = 0; $c < $num; $c++) {
                    $rows[$row][] = $data[$c];
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
     * @return array
     */
    function getAttributes($path) {
        $rows = $this->getRows(ltrim ($path,'/'), 'arff');
        $attributes = array();
        foreach($rows as $row){
            if(strpos($row[key($row)], '@attribute') === 0){
                $attr = explode(' ', $row[key($row)]);
                $attributes[] = $attr[1];
            }
        }

        return $attributes;
    }
} 