<?php
/**
 * Created by PhpStorm.
 * User: Deividas
 * Date: 14.3.13
 * Time: 16.09
 */

namespace Damis\DatasetsBundle\Twig;

use Twig_Extension;

class DatasetsExtension extends Twig_Extension{
    public function getFilters()
    {
        return array(
            'convert_bytes' => new \Twig_Filter_Method($this, 'convert_bytes'),
        );
    }

    public function convert_bytes($value)
    {
        $value = intval($value);
        if ($value < 512000){
            $value = $value / 1024.0;
            $ext = 'KB';
        }
        elseif ($value < 4194304000){
            $value = $value / 1048576.0;
            $ext = 'MB';
        }
        else{
            $value = $value / 1073741824.0;
            $ext = 'GB';
        }
        return sprintf('%s %s',round($value, 2), $ext);
    }

    public function getName()
    {
        return 'datasets_extension';
    }
} 