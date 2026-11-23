<?php

namespace Base\MainBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * `html_spaceless`: removes whitespace between HTML tags, exactly like Twig's `spaceless` filter, which is
 * deprecated since Twig 3.12 and removed in Twig 4. Kept because the experiment editor's component toolbox
 * (inline boxes, three per row) and the data grid depend on the missing whitespace.
 */
class HtmlExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('html_spaceless', $this->spaceless(...), ['is_safe' => ['html']]),
        ];
    }

    public function spaceless(?string $content): string
    {
        return trim((string) preg_replace('/>\s+</', '><', $content ?? ''));
    }
}
