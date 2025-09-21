<?php
namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CheckoutRequest
{
    #[Assert\NotBlank] public string $name;
    #[Assert\NotBlank] #[Assert\Regex('/^\d{4}$/')] public string $zip;
    #[Assert\NotBlank] public string $city;
    #[Assert\NotBlank] public string $address;
    #[Assert\NotBlank] public string $phone;
    #[Assert\NotBlank] #[Assert\Email] public string $email;

    /** @var array<array{id:int,name:string,price:int,qty:int}> */
    #[Assert\NotBlank] public array $items = [];

    #[Assert\PositiveOrZero] public int $total = 0;

    public static function fromArray(array $a): self {
        $o = new self();
        $o->name    = (string)($a['name'] ?? '');
        $o->zip     = (string)($a['zip'] ?? '');
        $o->city    = (string)($a['city'] ?? '');
        $o->address = (string)($a['address'] ?? '');
        $o->phone   = (string)($a['phone'] ?? '');
        $o->email   = (string)($a['email'] ?? '');
        $o->items   = is_array($a['items'] ?? null) ? $a['items'] : [];
        $o->total   = (int)($a['total'] ?? 0);
        return $o;
    }
}
