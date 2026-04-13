<?php

declare(strict_types=1);

namespace App\Http\Responses;

class ErrorApi
{
    public string $detail;

    public string $source;

    public function __construct(
        public int $status,
        public string $title,
        ?string $detail = null,
        ?string $source = null,
    ) {
        if ($detail !== null) {
            $this->detail = $detail;
        }
        if ($source !== null) {
            $this->source = $source;
        }
    }
}
