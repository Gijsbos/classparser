<?php
declare(strict_types=1);

#[Attribute()]
class TestAttribute
{
    public function __construct(private string $input)
    {}
}