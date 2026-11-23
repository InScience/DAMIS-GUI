<?php

namespace App\Tests\Unit;

use Base\MainBundle\Controller\RequestInputTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestInputTraitTest extends TestCase
{
    public function testQueryStringWinsOverPostBody(): void
    {
        $request = new Request(['id' => '1'], ['id' => '2', 'name' => 'posted']);

        self::assertSame('1', $this->input($request, 'id'));
        self::assertSame('posted', $this->input($request, 'name'));
    }

    public function testArraysAndDefaults(): void
    {
        $request = new Request([], ['ids' => ['3', '4']], ['ignored' => 'route attribute']);

        self::assertSame(['3', '4'], $this->input($request, 'ids'));
        self::assertNull($this->input($request, 'ignored'));
        self::assertSame('default', $this->input($request, 'missing', 'default'));
    }

    private function input(Request $request, string $key, mixed $default = null): mixed
    {
        $subject = new class {
            use RequestInputTrait {
                input as public;
            }
        };

        return $subject->input($request, $key, $default);
    }
}
