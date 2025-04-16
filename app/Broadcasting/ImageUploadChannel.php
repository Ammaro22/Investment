<?php

namespace App\Broadcasting;


use App\Traits\Imageable;

class ImageUploadChannel
{
    use Imageable;

    protected $images;
    protected $documents;
    protected $idImages;
    protected $propertyId;

    public function __construct($images, $documents, $idImages, $propertyId)
    {
        $this->images = $images;
        $this->documents = $documents;
        $this->idImages = $idImages;
        $this->propertyId = $propertyId;
    }

    public function handle()
    {
        if (!empty($this->images)) {
            $this->propertyImage($this->images, $this->propertyId);
        }

        if (!empty($this->documents)) {
            $this->propertyDocument($this->documents, $this->propertyId);
        }

        if (!empty($this->idImages)) {
            $this->idImage($this->idImages, $this->propertyId);
        }
    }
}
