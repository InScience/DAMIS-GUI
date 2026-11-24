<?php

namespace App\Tests\Unit;

use Base\MainBundle\Twig\HtmlExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class HtmlExtensionTest extends TestCase
{
    public function testWhitespaceBetweenTagsIsRemoved(): void
    {
        $twig = new Environment(new ArrayLoader(['t' => "{% apply html_spaceless %}\n  <div>\n    <b> a b </b>\n  </div>\n{% endapply %}"]));
        $twig->addExtension(new HtmlExtension());

        self::assertSame('<div><b> a b </b></div>', $twig->render('t'));
    }

    public function testNullIsEmpty(): void
    {
        self::assertSame('', (new HtmlExtension())->spaceless(null));
    }
}
