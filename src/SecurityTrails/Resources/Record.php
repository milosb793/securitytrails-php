<?php

namespace SecurityTrails\Resources;


class Record
{
    private $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;

        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJSON()
    {
        return json_encode($this->toArray());
    }

}