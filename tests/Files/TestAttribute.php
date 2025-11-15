<?php
declare(strict_types=1);

use Attribute;

#[Attribute()]
class TestAttribute
{
    public function __construct(private string $input)
    {}
}