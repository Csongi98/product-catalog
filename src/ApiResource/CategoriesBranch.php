<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\ApiResource\State\CategoriesBranchProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/branch',
            provider: CategoriesBranchProvider::class,
            output: false
        ),
    ]
)]
final class CategoriesBranch
{
}
