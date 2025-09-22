<?php
namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\CheckoutRequest;
use App\Dto\CheckoutResult;
use App\ApiResource\State\CheckoutProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/checkout',
            input: CheckoutRequest::class,
            output: CheckoutResult::class,
            processor: CheckoutProcessor::class,
            read: false
        ),
    ]
)]
final class Checkout {}
