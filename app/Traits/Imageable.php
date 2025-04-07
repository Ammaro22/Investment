<?php

namespace App\Traits;
use App\Models\Image;
//use http\Env\Request;

trait Imageable
{
    public static function ssave($getImages,$id)
    {
        foreach($getImages as $getImage) {
            $filename = $getImage->getClientOriginalName();
            $name = pathinfo($filename, PATHINFO_FILENAME) . '' . time() . '.' . $getImage->getClientOriginalExtension();
            $path = 'items';
            $getImage->move($path, $name);
            $save = Image::create([
                'name' => $name,
                'path' => $path,
                'item_id' => $id
            ]);
        }


    }
    public static  function ss($getImage){
    $filename=$getImage->getClientOriginalName();
    $name = pathinfo($filename, PATHINFO_FILENAME).''.time().'.'.$getImage->extension();
    $path = 'users';
    $getImage->move($path, $name);
    return $name;
}
    public static  function sss($getImage){
        $filename=$getImage->getClientOriginalName();
        $name = pathinfo($filename, PATHINFO_FILENAME).''.time().'.'.$getImage->extension();
        $path = 'comments';
        $getImage->move($path, $name);
        return $name;
    }
    public static  function ssss($getImage){
        $filename=$getImage->getClientOriginalName();
        $name = pathinfo($filename, PATHINFO_FILENAME).''.time().'.'.$getImage->extension();
        $path = 'products';
        $getImage->move($path, $name);
        return $name;
    }

}








