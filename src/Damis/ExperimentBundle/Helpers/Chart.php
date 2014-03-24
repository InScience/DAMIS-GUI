<?php
/**
 * Created by PhpStorm.
 * User: Karolis
 * Date: 14.3.20
 * Time: 17.26
 */

namespace Damis\ExperimentBundle\Helpers;

use Base\ConvertBundle\Helpers\ReadFile;

class Chart {

    public function classifieData($fileUrl, $x, $y, $clsCol) {
        $minX = null;
        $minY = null;
        $maxX = null;
        $maxY = null;
        $minCls = null;
        $maxCls = null;
        $clsType = null;
        $arffCls = null;
        $attributes = [];
        $maxClasses = 120;
        $error = null;
        $helper = new ReadFile();
        $result = [];

        foreach($helper->getAttributes($fileUrl, true) as $key => $attr){
            $colName = $attr['name'];
            $colType = $attr['type'];
            $attributes[] = [$attr];
            if($x == null && $colType != 'string')
                $x = $key;

            elseif($y == null && $colType != 'string')
                $y = $key;

            if($colType == 'class')
                $arffCls = $key;
        }

        if($clsCol == null) {
            if($arffCls != null)
                $clsCol = $arffCls;
            else
                if(count($helper->getAttributes($fileUrl, true)) > 0)
                    $clsCol = count($helper->getAttributes($fileUrl, true)) - 1;
        }

        if($clsCol != null)
            $clsType = $helper->getAttributes($fileUrl, true)[$clsCol]['type'];

        $data = false;
        foreach ($helper->getRows($fileUrl, 'arff') as $row) {
            if(!$data) {
                if($row[0] == '@data')
                    $data = true;
                continue;
            }

            if($minX === null || (float) $row[$x] < $minX)
                $minX = (float) $row[$x];
            if($minY === null || (float) $row[$y] < $minY)
                $minY = (float) $row[$y];
            if($maxX === null || (float) $row[$x] > $maxX)
                $maxX = (float) $row[$x];
            if($maxY === null || (float) $row[$y] > $maxY)
                $maxY = (float) $row[$y];

            if($clsType != "string"){
                if ($minCls == null or (float) $row[$clsCol] < $minCls)
                        $minCls = (float) $row[$clsCol];
                if ($maxCls == null or (float) $row[$clsCol] > $maxCls)
                        $maxCls = (float) $row[$clsCol];
            }

            if (!($clsType == "string" or $clsType == "integer"))
                continue;
            $classCell = $row[$clsCol];

            $result[$classCell][] = [$row[$x], $row[$y]];
        }

        if($clsType != 'string' and $clsType != 'integer') {
            $step = 1 * ($maxCls - $minCls) / $maxClasses;
            $groups = [];
            foreach(range($minCls, $maxCls, $step) as $group)
                $groups[] = $group + ' - ' + ($group + $step);

            $data = false;
            foreach ($helper->getRows($fileUrl, 'arff') as $row) {
                if(!$data) {
                    if($row[0] == '@data')
                        $data = true;
                    continue;
                }
                $value = $row[$clsCol];

                $groupNo = (int) ((1 * ($value - $minCls) * $maxClasses) / ($maxCls - $minCls));
                if($groupNo == count($groups))
                    $groupNo--;

                $cls = $groups[$groupNo];
                $result[$cls][] = [$row[$x], $row[$y]];
            }
        }

        $_result = [];
        foreach($result as $key => $group) {
            $_result[] = [
                'group' => str_replace('\'', '', $key),
                'data' => $group
            ];
        }

        return [
            'attributes' => $attributes,
            'content' => [
                'data' => $_result,
                'minX' => $minX,
                'minY' => $minY,
                'maxX' => $maxX,
                'maxY' => $maxY,
                'minCls' => $minCls,
                'maxCls' => $maxCls,
            ],
            'x' => $x,
            'y' => $y,
            'clsCol' => $clsCol
        ];
    }
}