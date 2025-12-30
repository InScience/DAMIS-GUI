<?php
// src/Service/CompatibleTemplateLocator.php
namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

class CompatibleTemplateLocator extends TemplateLocator
{
    protected $locator;  // Changed from private to protected
    protected $cache = [];  // Changed from private to protected

    public function __construct(FileLocatorInterface $locator)
    {
        $this->locator = $locator;
        $this->cache = [];
        // Don't call parent to avoid issues
    }

    public function locate($template, $currentPath = null, $first = true)
    {
        // Convert template reference to string path
        if ($template instanceof TemplateReferenceInterface) {
            // Just return the logical name - don't try to resolve it
            return $template->getLogicalName();
        }
        
        // Return as-is for strings
        return (string) $template;
    }
    
    // Override getCacheKey to avoid bundle issues
    protected function getCacheKey($template)
    {
        if (is_string($template)) {
            return $template;
        }
        
        if ($template instanceof TemplateReferenceInterface) {
            return $template->getLogicalName();
        }
        
        return '';
    }
}