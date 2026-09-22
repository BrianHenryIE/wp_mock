<?php

namespace WP_Mock\Tests\Mocks;

enum SampleEnum
{
    case Code;

    public function method(): void
    {
    }

    public static function staticMethod(): void
    {
    }
}
