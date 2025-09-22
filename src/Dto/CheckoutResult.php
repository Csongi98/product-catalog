<?php
namespace App\Dto;

class CheckoutResult
{
    public function __construct(
        public bool $ok,
        public string $orderNo
    ) {}
}
