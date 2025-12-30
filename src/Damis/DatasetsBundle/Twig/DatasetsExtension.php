<?php
/**
 * Created by PhpStorm.
 * User: Deividas
 * Date: 14.3.13
 * Time: 16.09
 * Updated for Twig 2.x
 */

namespace Damis\DatasetsBundle\Twig;

// These are the new classes for Twig 2.x
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

// We now extend AbstractExtension instead of Twig_Extension
class DatasetsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('convert_bytes', $this->convertBytes(...)),
        ];
    }

    public function convertBytes($value): string
    {
        $value = intval($value);
        if ($value < 512000) {
            $value = $value / 1024.0;
            $ext = 'KB';
        } elseif ($value < 4194304000) {
            $value = $value / 1048576.0;
            $ext = 'MB';
        } else {
            $value = $value / 1073741824.0;
            $ext = 'GB';
        }
        return sprintf('%s %s', round($value, 2), $ext);
    }
}