<?php

namespace Hwkdo\D3RestLaravel\DTO;

class TempUploadDTO
{    

    public function __construct(      
        public bool $success,
        public ?string $message,
        public ?string $filename,
        public ?string $location
    )
    {
        $this->success = $success;
        $this->message = $message;
        $this->filename = $filename;
        $this->location = $location;
    }
}