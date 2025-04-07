<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    public function store(Request $request)
    {
$validator = Validator::make($request->all(), ['file' => 'required|mimes:png,jpg,jpeg,gif|max:2048',]);
if ($validator->fails())
{

return response()->json(['error' => $validator->errors()], 401);
}

$getImage = $request->file;
$name = time() . '.' . $getImage->extension();
$path = 'public/files';

$save = Image::create([
    'name' => $name,
    'path' => $path
]);
$getImage->move($path, $name);


return response()->json([
    "success" => true,
    "message" => "File successfully uploaded",
    "file" => $getImage
]);
}
}

