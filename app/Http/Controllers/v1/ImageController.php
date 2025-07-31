<?php

namespace App\Http\Controllers\v1;

use App\Models\id_image;
use App\Models\Property_document;
use App\Models\Property_image;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ImageController extends BaseController
{
    public function deletePropertyImage(Request $request, $id)
    {

        if (!$request->user() || $request->user()->type !== 'Admin') {
            return response()->json([
                'message' => __('messages.operation_failed'),
                'error' => 'messages.Unauthorized'
            ], 403);
        }

        $image = Property_image::find($id);

        if (!$image) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }
        $filePath = public_path('property_image/' . basename($image->path));

        Log::info("Attempting to delete file: " . $filePath);

        if (File::exists($filePath)) {
            if (File::delete($filePath)) {
                Log::info("Successfully deleted: " . $filePath);
            } else {
                Log::warning("Failed to delete: " . $filePath);
            }
        } else {
            Log::warning("File not found for deletion: " . $filePath);
        }


        $image->delete();

        return response()->json(['message' => __('messages.delete_success')], 200);
    }

    public function deletePropertyDocument(Request $request, $id)
    {

        if (!$request->user() || $request->user()->type !== 'Admin') {
            return response()->json([
                'message' => __('messages.operation_failed'),
                'error' => 'messages.Unauthorized'
            ], 403);
        }

        $image = Property_document::find($id);

        if (!$image) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }
        $filePath = public_path('property_document/' . basename($image->path));

        Log::info("Attempting to delete file: " . $filePath);

        if (File::exists($filePath)) {
            if (File::delete($filePath)) {
                Log::info("Successfully deleted: " . $filePath);
            } else {
                Log::warning("Failed to delete: " . $filePath);
            }
        } else {
            Log::warning("File not found for deletion: " . $filePath);
        }


        $image->delete();

        return response()->json(['message' => __('messages.delete_success')], 200);
    }

    public function deleteIdImage(Request $request, $id)
    {

        if (!$request->user() || $request->user()->type !== 'Admin') {
            return response()->json([
                'message' => __('messages.operation_failed'),
                'error' => 'messages.Unauthorized'
            ], 403);
        }

        $image = id_image::find($id);

        if (!$image) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }
        $filePath = public_path('id_image/' . basename($image->path));

        Log::info("Attempting to delete file: " . $filePath);

        if (File::exists($filePath)) {
            if (File::delete($filePath)) {
                Log::info("Successfully deleted: " . $filePath);
            } else {
                Log::warning("Failed to delete: " . $filePath);
            }
        } else {
            Log::warning("File not found for deletion: " . $filePath);
        }


        $image->delete();

        return response()->json(['message' => __('messages.delete_success')], 200);
    }
}

