<?php

namespace App\Http\Responses;

class ApiResponse
{
    public mixed $data;

    public array $errors;

    public array $meta;

    public function addError(ErrorApi $error): self
    {
        $this->errors[] = $error;

        return $this;
    }
}
