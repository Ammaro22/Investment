<?php

namespace App\Http\Controllers;
use App\Models\EmployeeInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class EmployeeInformationController extends Controller
{

    public function addEmployeeInformation(Request $request, $userId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'current_address' => 'required|string|max:255',
            'front_id_image' => 'required|image|mimes:jpg,jpeg,png',
            'back_id_image' => 'required|image|mimes:jpg,jpeg,png',
            'date_of_birth' => 'required|date',
            'personal_photo' => 'required|image|mimes:jpg,jpeg,png'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $frontIdImagePath = null;
        if ($request->hasFile('front_id_image')) {
            $frontIdImage = $request->file('front_id_image');
            $frontIdImageName = time() . '_front_' . $frontIdImage->getClientOriginalName();
            $frontIdImage->move(public_path('images/front'), $frontIdImageName);
            $frontIdImagePath = "images/front/$frontIdImageName";
        }

        $backIdImagePath = null;
        if ($request->hasFile('back_id_image')) {
            $backIdImage = $request->file('back_id_image');
            $backIdImageName = time() . '_back_' . $backIdImage->getClientOriginalName();
            $backIdImage->move(public_path('images/back'), $backIdImageName);
            $backIdImagePath = "images/back/$backIdImageName";
        }


        $employeeInfo = new EmployeeInformation();
        $employeeInfo->user_id = $userId;
        $employeeInfo->current_address = $request->current_address;
        $employeeInfo->front_id_image = $frontIdImagePath;
        $employeeInfo->back_id_image = $backIdImagePath;
        $employeeInfo->date_of_birth = $request->date_of_birth;
        $employeeInfo->save();

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        if ($request->hasFile('personal_photo')) {
            $personalPhoto = $request->file('personal_photo');
            $personalPhotoName = time() . '_personal_' . $personalPhoto->getClientOriginalName();
            $personalPhoto->move(public_path('images/personal'), $personalPhotoName);
            $user->personal_photo = "images/personal/$personalPhotoName";
            $user->save();
        }

        return response()->json([
                'message' => trans('messages.operation_success'),
            'data' => [
                'employee_info' => $employeeInfo,
                'user' => $user,
            ]
        ]);
    }

    public function searchUsers(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $name = $request->input('name');
        $roleId = $request->input('role_id');

        $users = User::where('name', 'like', '%' . $name . '%')
            ->where('role_id', $roleId)
            ->get();

        return response()->json([
            'message' => trans('messages.user_search_success'),
            'data' => $users,
        ]);
    }

    public function getUserWithEmployeeInfo($userId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $user = User::with('EmployeeInformation')->find($userId);

        if (!$user) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        // إعادة ترتيب المعلومات
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role_id' => $user->role_id,
            'active'=>$user->active,
            'personal_photo' => $user->personal_photo,
            'current_address' => $user->EmployeeInformation->current_address ?? null,
            'front_id_image' => $user->EmployeeInformation->front_id_image ?? null,
            'back_id_image' => $user->EmployeeInformation->back_id_image ?? null,
            'date_of_birth' => $user->EmployeeInformation->date_of_birth ?? null,
        ];

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $userData,
        ]);
    }


    public function getUsersByRoleAndActive(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $roleId = $request->input('role_id');
        $active = $request->input('active');

        $users = User::where('role_id', $roleId)
            ->where('active', $active)
            ->get();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $users,
        ]);
    }


    public function deactivateUser($userId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $user->active = false;
        $user->save();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $user,
        ]);
    }

    public function activateUser($userId)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        $user->active = true;
        $user->save();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $user,
        ]);
    }

}
