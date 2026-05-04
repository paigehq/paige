<?php

namespace App\Exceptions;

use RuntimeException;

class PlanLimitException extends RuntimeException
{
    public function __construct(
        public readonly string $limit,
        public readonly string $plan,
    ) {
        parent::__construct("Plan limit reached: cannot add more $limit on the $plan plan.");
    }
}
