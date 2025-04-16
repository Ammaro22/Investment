<?php

namespace App\Traits;
use App\Models\id_image;
use App\Models\Property_document;
use App\Models\Property_image;
//use http\Env\Request;

trait Imageable
{

    public static function propertyImage($getImages, $id)
    {
        foreach ($getImages as $getImage) {
            $filename = $getImage->getClientOriginalName();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '' . time() . '.' . $getImage->getClientOriginalExtension();
            $path = 'Property_image' ;
            $getImage->move($path, $name);
            Property_image::create([
                'name' => $name,
                'path' => $path . '/' . $name,
                'property_for_sale_id' => $id
            ]);
        }
    }

    public static function propertyDocument($getImages,$id)
    {
        foreach($getImages as $getImage) {
            $filename = $getImage->getClientOriginalName();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '' . time() . '.' . $getImage->getClientOriginalExtension();
            $path = 'Property_document';
            $getImage->move($path, $name);
           Property_document::create([
                'name' => $name,
               'path' => $path . '/' . $name,
                'property_for_sale_id' => $id
            ]);
        }


    }

    public static function idImage($getImages,$id)
    {
        foreach($getImages as $getImage) {
            $filename = $getImage->getClientOriginalName();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '' . time() . '.' . $getImage->getClientOriginalExtension();
            $path = 'id_image';
            $getImage->move($path, $name);
             id_image::create([
                'name' => $name,
                 'path' => $path . '/' . $name,
                'property_for_sale_id' => $id
            ]);
        }


    }

}








