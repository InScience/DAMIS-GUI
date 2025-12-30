<?php
/**
 * Created by PhpStorm.
 * User: Deividas
 * Date: 14.3.12
 * Time: 10.34
 */

namespace Base\ConvertBundle\Helpers;

use Base\UserBundle\Entity\User;
use Damis\DatasetsBundle\Entity\Dataset;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

class ReadFile
{

    /**
     * Reads file and returns rows
     *
     * @param String $path
     * @param String $format
     * @return array
     */
    function getRows($path, $format)
    {
        $row = 0;
        $rows = [];
        $memoryLimit = ini_get('memory_limit');
        $suffix = '';
        sscanf($memoryLimit, '%u%c', $number, $suffix);
        if (isset($suffix)) {
            $number = $number * 1024 ** strpos(' KMG', (string) $suffix);
        }
        if (memory_get_usage(true) + filesize($path) * 5.8 > $number) {
            return false;
        }
        if ($format == 'tab') {
            $delimiter = "\t";
        } elseif ($format == 'arff')
            $delimiter = ",";
        else {
            $delimiters = ['comma'     => ",", 'semicolon' => ";", 'space'     => " ", 'tab'       => "\t"];
            $content = file_get_contents($path);
            $content = explode("\n", $content);
            // Find most often delimeter
            foreach ($delimiters as $key => $delim) {
                $res[$key] = substr_count(trim($content[(int) floor(count($content)/ 2)]), $delim);
            }
            arsort($res);
            $first_key = array_key_first($res);

            $delimiter = $delimiters[$first_key];
        }

        if (($handle = fopen($path, "r")) !== false) {
            while (($data = fgetcsv($handle, null, $delimiter)) !== false) {
                if (memory_get_usage(true) > $number - 1000000) {
                    return false;
                }

                $num = count($data);
                $row++;
                for ($c = 0; $c < $num; $c++) {
                    // String should be not empty. If string is '0', empty function return true
                   $rows[$row][] = trim((string) $data[$c]);
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Returns files attributes
     *
     * @param String  $path
     * @param boolean $withType True - returning associative array with type and attribute name
     * @return array
     */
    function getAttributes($path, $withType = false)
    {
        $rows = $this->getRows($path, 'arff');
        $attributes = [];
        foreach ($rows as $row) {
            if (str_starts_with(strtolower((string) $row[key($row)]), '@attribute')) {
                $str = preg_replace('/\s+/i', " ", (string) $row[key($row)]);
                $attr = explode(' ', (string) $str);
                if (!$withType) {
                    $attributes[] = $attr[1];
                } else {
                    $attributes[] = ['type' => strtolower($attr[2]), 'name' => $attr[1]];
                }
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
    function getClassAttr($path)
    {
        $rows = $this->getRows($path, 'arff');
        $attributes = [];
        foreach ($rows as $row) {
            if (str_starts_with(strtolower((string) $row[key($row)]), '@attribute class')) {
                $str = preg_replace('/\s+/i', " ", (string) $row[key($row)]);
                $attr = explode(' ', (string) $str);
                $attributes[] = $attr[1].'_attr';
            } elseif (str_starts_with(strtolower((string) $row[key($row)]), '@attribute')) {
                $str = preg_replace('/\s+/i', " ", (string) $row[key($row)]);
                $attr = explode(' ', (string) $str);
                $attributes[] = $attr[1];
            }
        }
        return $attributes;
    }

    /**
     * Function will calculate a posible range for rows file
     * @param type $rows
     * @param type $classNr
     * @return array
     */
    public function getClassRange($rows, $classNr)
    {
        $range = [];
        foreach ($rows as $row) {
            if (str_starts_with(strtolower((string) $row[0]), '@attribute') ||
                str_starts_with(strtolower((string) $row[0]), '@data') ||
                str_starts_with(strtolower((string) $row[0]), '@relation') ||
                $row[0] == '%') {
                continue;
            } else {
                $range[] = $row[$classNr];
            }
        }
        return array_unique($range, SORT_REGULAR);
    }

    /**
     * Select dataset features. Used in select features component
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
        $em = $container->get('doctrine')->getManager();
        $dataset = $em->getRepository(Dataset::class)->findOneByDatasetId($datasetId);
        $projectDir = $container->getParameter('kernel.project_dir');
        $rows = @$this->getRows($projectDir . '/public' . $dataset->getFilePath(), 'arff');
        $nr = 0;
        $file = '';

        if (!in_array($class, $attr) && $class != "") {
            $attrs = $attr;
            $attrs[] = $class;
        } else {
            $attrs = $attr;
        }

        foreach ($rows as $row) {
            if (str_starts_with(strtolower((string) $row[0]), '@attribute')) {
                if ($nr == $class && $class != "") {
                    if (str_starts_with(strtolower((string) $row[0]), '@attribute class')) {
                        // Set class atributes
                        $classAttributes = preg_replace('/.*\{(.*)\}.*/i', "{\\1}", implode(', ', $row));
                        $file .= '@attribute class '.$classAttributes;
                    } else {
                        // We get all posible values of attribute
                        $file .= '@attribute class {'.implode(',', $this->getClassRange(@$rows, $class)).'}';
                    }
                    $file .= PHP_EOL;
                } elseif (in_array($nr, $attr)) {
                    foreach ($row as $value) {
                        $file .= $value;
                    }
                    $file .= PHP_EOL;
                }
                $nr++;
            } elseif (str_starts_with(strtolower((string) $row[0]), '@data') ||
                        str_starts_with(strtolower((string) $row[0]), '@relation')) {
                foreach ($row as $value) {
                    $file .= $value;
                }
                $file .= PHP_EOL;
             // Ignore comments
            } elseif ($row[0] == '%') {
                continue;
            } else {
                $rowData = [];
                foreach ($attrs as $at) {
                    // Check if the index exists in the row
                    if (isset($row[$at])) {
                        $rowData[] = $row[$at];
                    }
                }
                // Only write the row if we have data
                if (!empty($rowData)) {
                    $file .= implode(',', $rowData) . PHP_EOL;
                }
            }
        }
        $temp_folder = $container->getParameter("kernel.cache_dir");
        $temp_file = $temp_folder.'/'.basename($dataset->getDatasetTitle().time().'.arff');

        $filew = fopen($temp_file, "w");
        fwrite($filew, $file);
        fclose($filew);

        $file = new File($temp_file);
        $user = $em->getRepository(User::class)->findOneById($userId);
        $file_entity = new Dataset();
        $file_entity->setUser($user);
        $file_entity->setDatasetTitle('experiment result');
        $file_entity->setDatasetCreated(time());
        $file_entity->setDatasetIsMidas(false);
        $file_entity->setHidden(true);
        $em->persist($file_entity);
        $em->flush();

        // Manual file save replacement
        $projectDir = $container->getParameter('kernel.project_dir');
        $targetDir = $projectDir . '/public/uploads/datasets';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $originalName = $file->getFilename();
        $newName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $originalName);
        
        $file->move($targetDir, $newName);
        
        $relativePath = '/uploads/datasets/' . $newName;
        $file_entity->setFilePath($relativePath);
        $file_entity->setFile(['path' => $relativePath]);

        $em->flush();

        // $ref_class = new ReflectionClass(Dataset::class);
        // $mapping = $container->get('iphp.filestore.mapping.factory')->getMappingFromField($file_entity, $ref_class, 'file');
        // $file_data = $container->get('iphp.filestore.filestorage.file_system')->upload($mapping, $file);
        // $file_entity->setFile($file_data);
        // $file_entity->setFilePath($file_data['path']);
        // $em->flush();

        // $file->move() already moved the file, so original temp file is gone.
        // @unlink($temp_file);

        return $file_entity->getDatasetId();

    }
}

