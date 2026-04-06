<?php

namespace App\Http\Response;

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
