<?php

namespace App\Http\Controllers\v1;

use App\Broadcasting\ImageUploadChannel;
use App\Models\Property_for_sale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\Requests;

class PropertyController extends BaseController
{
    public function store(Request $request)
    {

        $userId = $request->user()->id;
        $validator = Validator::make($request->all(),[
            'property_type' => 'required|string|max:255',
            'area' => 'required|numeric|min:0',
            'number_of_rooms' => 'nullable|integer|min:0',
            'number_of_bathrooms' => 'nullable|integer|min:0',
            'property_age' => 'nullable|numeric|min:0',
            'decoration' => 'nullable|string|max:255',
            'kitchen_type' => 'nullable|string|max:255',
            'flooring_type' => 'nullable|string|max:255',
            'overlook_from' => 'nullable|numeric|min:0',
            'balcony_size' => 'nullable|numeric|min:0',
            'painting_type' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'pay_way' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'contract'=>'required|string|max:255',
            'exact_position' => 'required|string|max:255',
            'property_images' => 'required|array',
            'property_images.*' => 'image',
            'property_documents' => 'required|array',
            'property_documents.*' => 'image',
            'id_images' => 'required|array',
            'id_images.*' => 'image',]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $property = Property_for_sale::create([
            'user_id' => $userId,
            'property_type' => $request->property_type,
            'area' => $request->area,
            'number_of_rooms' => $request->number_of_rooms,
            'number_of_bathrooms' => $request->number_of_bathrooms,
            'property_age' => $request->property_age,
            'decoration' => $request->decoration,
            'kitchen_type' => $request->kitchen_type,
            'flooring_type' => $request->flooring_type,
            'overlook_from' => $request->overlook_from,
            'balcony_size' => $request->balcony_size,
            'painting_type' => $request->painting_type,
            'price' => $request->price,
            'pay_way' => $request->pay_way,
            'state' => $request->state,
            'contract' => $request->contract,
            'exact_position' => $request->exact_position,
            'legal_check' => false,
            'expert_check' => false,
            'accept' => false,
            'status' => 'معلق'
        ]);


                $propertyid=$property->id;

        dispatch(new ImageUploadChannel(
            $request->file('property_images'),
            $request->file('property_documents'),
            $request->file('id_images'),
            $propertyid
        ));

        $requestData = [
            'property_for_sale_id' => $propertyid,
            'status' => 'معلق',
            'type_request' => 'legal check',
            'description' => $request->input('description', 'طلب جديد لعقار'),
        ];
        Requests::create($requestData);

        $this->firebaseNotification->sendToUser($userId,'create_property_for_sale');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $property,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;

        $property = Property_for_sale::find($id);

        if (!$property) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        if ($property->user_id !== $userId) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'property_type' => 'sometimes|string|max:255',
            'area' => 'sometimes|numeric|min:0',
            'number_of_rooms' => 'sometimes|integer|min:0',
            'number_of_bathrooms' => 'sometimes|integer|min:0',
            'property_age' => 'sometimes|numeric|min:0',
            'decoration' => 'sometimes|string|max:255',
            'kitchen_type' => 'sometimes|string|max:255',
            'flooring_type' => 'sometimes|string|max:255',
            'overlook_from' => 'sometimes|numeric|min:0',
            'balcony_size' => 'sometimes|numeric|min:0',
            'painting_type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'pay_way' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'contract' => 'sometimes|string|max:255',
            'exact_position' => 'sometimes|string|max:255',
            'property_images' => 'array|nullable',
            'property_images.*' => 'image',
            'property_documents' => 'array|nullable',
            'property_documents.*' => 'image',
            'id_images' => 'array|nullable',
            'id_images.*' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }


        $property->update(array_filter($request->only([
            'property_type',
            'area',
            'number_of_rooms',
            'number_of_bathrooms',
            'property_age',
            'decoration',
            'kitchen_type',
            'flooring_type',
            'overlook_from',
            'balcony_size',
            'painting_type',
            'price',
            'pay_way',
            'contract',
            'state',
            'exact_position',
        ])));

        if ($request->hasFile('property_images') || $request->hasFile('property_documents') || $request->hasFile('id_images')) {
            dispatch(new ImageUploadChannel(
                $request->file('property_images'),
                $request->file('property_documents'),
                $request->file('id_images'),
                $property->id
            ));
        }
        $property->load('Property_image', 'Property_document', 'id_image');

        $this->firebaseNotification->sendToUser($userId,'update_property');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'property' => $property,
                'images' => $property->Property_image,
                'documents' => $property->Property_document,
                'id_images' => $property->id_image,
            ],
        ], 200);
    }

    public function updatebyadmin(Request $request, $id)
    {
        $user = auth()->user();
        $userRole = $user->role_id;

        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $property = Property_for_sale::find($id);

        if (!$property) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }


        $validator = Validator::make($request->all(), [
            'property_type' => 'sometimes|string|max:255',
            'area' => 'sometimes|numeric|min:0',
            'number_of_rooms' => 'sometimes|integer|min:0',
            'number_of_bathrooms' => 'sometimes|integer|min:0',
            'property_age' => 'sometimes|numeric|min:0',
            'decoration' => 'sometimes|string|max:255',
            'kitchen_type' => 'sometimes|string|max:255',
            'flooring_type' => 'sometimes|string|max:255',
            'overlook_from' => 'sometimes|numeric|min:0',
            'balcony_size' => 'sometimes|numeric|min:0',
            'painting_type' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'pay_way' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'contract' => 'sometimes|string|max:255',
            'exact_position' => 'sometimes|string|max:255',
            'property_images' => 'array|nullable',
            'property_images.*' => 'image',
            'property_documents' => 'array|nullable',
            'property_documents.*' => 'image',
            'id_images' => 'array|nullable',
            'id_images.*' => 'image',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }


        $property->update(array_filter($request->only([
            'property_type',
            'area',
            'number_of_rooms',
            'number_of_bathrooms',
            'property_age',
            'decoration',
            'kitchen_type',
            'flooring_type',
            'overlook_from',
            'balcony_size',
            'painting_type',
            'price',
            'pay_way',
            'contract',
            'state',
            'exact_position',
        ])));

        if ($request->hasFile('property_images') || $request->hasFile('property_documents') || $request->hasFile('id_images')) {
            dispatch(new ImageUploadChannel(
                $request->file('property_images'),
                $request->file('property_documents'),
                $request->file('id_images'),
                $property->id
            ));
        }
        $property->load('Property_image', 'Property_document', 'id_image');

        $this->firebaseNotification->sendToUser($user,'update_property');

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'property' => $property,
                'images' => $property->Property_image,
                'documents' => $property->Property_document,
                'id_images' => $property->id_image,
            ],
        ], 200);
    }

    public function destroy($id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 2 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $property = Property_for_sale::findOrFail($id);

        foreach ($property->property_image as $image) {
            $filePath = public_path('property_image/' . basename($image->path));

            \Log::info("Attempting to delete file: " . $filePath);

            if (File::exists($filePath)) {
                if (File::delete($filePath)) {
                    \Log::info("Successfully deleted: " . $filePath);
                } else {
                    \Log::warning("Failed to delete: " . $filePath);
                }
            } else {
                \Log::warning("File not found for deletion: " . $filePath);
            }

            $image->delete();
        }

        foreach ($property->property_document as $document) {
            $filePath = public_path('property_document/' . basename($document->path));

            \Log::info("Attempting to delete file: " . $filePath);

            if (File::exists($filePath)) {
                if (File::delete($filePath)) {
                    \Log::info("Successfully deleted: " . $filePath);
                } else {
                    \Log::warning("Failed to delete: " . $filePath);
                }
            } else {
                \Log::warning("File not found for deletion: " . $filePath);
            }

            $document->delete();
        }

        foreach ($property->id_image as $idImage) {
            $filePath = public_path('id_image/' . basename($idImage->path));

            \Log::info("Attempting to delete file: " . $filePath);

            if (File::exists($filePath)) {
                if (File::delete($filePath)) {
                    \Log::info("Successfully deleted: " . $filePath);
                } else {
                    \Log::warning("Failed to delete: " . $filePath);
                }
            } else {
                \Log::warning("File not found for deletion: " . $filePath);
            }

            $idImage->delete();
        }
        $property->delete();

        return response()->json([
            'message' => trans('messages.delete_success'),
        ], 200);
    }

    public function getPropertiesByToken(Request $request)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $properties = Property_for_sale::where('user_id', $user->id)->get();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $properties,
        ], 200);
    }

    public function getPropertyById($id)
    {

        $property = Property_for_sale::find($id);

        if (!$property) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => $property,
        ], 200);
    }

    public function getImageForPropertyById($id)
    {
        $property = Property_for_sale::with(['Property_image', 'Property_document', 'id_image'])->find($id);

        if (!$property) {
            return response()->json(['message' => __('messages.not_found')], 404);
        }

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => [
                'Property_image' => $property->Property_image,
                'Property_document' => $property->Property_document,
                'id_images' => $property->id_image,
            ],
        ], 200);
    }

    public function show($id)
    {
        $user = auth()->user();
        if (!$user || $user->role_id != 3) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $properties = Property_for_sale::where('user_id', $id)->get('id');

        if ($properties->isNotEmpty()) {
            $formatted = $properties->map(function($item) {
                return ['property_id' => $item->id];
            });

            return response()->json([
                'message' => trans('messages.operation_success'),
                'data' => $formatted
            ]);
        } else {
            return response()->json(['message' => trans('messages.not_found')], 404);
        }
    }


}
