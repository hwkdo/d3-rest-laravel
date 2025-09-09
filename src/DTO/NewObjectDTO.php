<?php

namespace Hwkdo\D3RestLaravel\DTO;

class NewObjectDTO
{    

    public function __construct(  
        public bool $success,
        public ?string $message,
        public ?string $location,
        public ?string $id
    )
    {
        $this->success = $success;
        $this->message = $message;
        $this->location = $location;
        $this->id = $id;
    }
}