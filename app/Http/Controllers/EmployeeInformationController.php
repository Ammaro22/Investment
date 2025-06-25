<?php

namespace App\Http\Controllers;
use App\Models\EconomicEvaluation;
use App\Models\EmployeeInformation;
use App\Models\Property_for_sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
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
            'father_name' => 'required|string|max:255',
            'mother_name' => 'required|string|max:255',
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
        $employeeInfo->father_name = $request->father_name;
        $employeeInfo->mother_name = $request->mother_name;
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


    public function updateEmployeeInformation(Request $request, $userId)
    {
        $user = auth()->user();

        if (!$user || $user->role_id != 1) {
            return response()->json(['message' => trans('messages.unauthorized')], 403);
        }

        $employeeUser = User::where('id', $userId)
            ->whereIn('role_id', [3, 4])
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|min:4|max:255',
            'email' => 'nullable|string|email|unique:users,email,' . $employeeUser->id,
            'phone' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|in:3,4',
            'active' => 'nullable|boolean',
            'personal_photo' => 'nullable|image|mimes:jpg,jpeg,png',

            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'current_address' => 'nullable|string|max:255',
            'front_id_image' => 'nullable|image|mimes:jpg,jpeg,png',
            'back_id_image' => 'nullable|image|mimes:jpg,jpeg,png',
            'date_of_birth' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $userdata = $request->only(['name', 'email', 'phone', 'role_id', 'active']);

        if ($request->filled('password')) {
            $userdata['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('personal_photo')) {
            if ($employeeUser->personal_photo && file_exists(public_path($employeeUser->personal_photo))) {
                unlink(public_path($employeeUser->personal_photo));
            }

            $personalPhoto = $request->file('personal_photo');
            $personalPhotoName = time() . '_personal_' . $personalPhoto->getClientOriginalName();
            $personalPhoto->move(public_path('images/personal'), $personalPhotoName);
            $userdata['personal_photo'] = "images/personal/$personalPhotoName";
        }

        $employeeUser->update($userdata);

        $employeeInfo = $request->only(['father_name', 'mother_name', 'current_address', 'date_of_birth']);

        if ($request->hasFile('front_id_image')) {
            if ($employeeUser->employeeInformation && $employeeUser->employeeInformation->front_id_image && file_exists(public_path($employeeUser->employeeInformation->front_id_image))) {
                unlink(public_path($employeeUser->employeeInformation->front_id_image));
            }

            $front = $request->file('front_id_image');
            $frontName = time() . '_front_' . $front->getClientOriginalName();
            $front->move(public_path('images/front'), $frontName);
            $employeeInfo['front_id_image'] = "images/front/$frontName";
        }

        if ($request->hasFile('back_id_image')) {
            if ($employeeUser->employeeInformation && $employeeUser->employeeInformation->back_id_image && file_exists(public_path($employeeUser->employeeInformation->back_id_image))) {
                unlink(public_path($employeeUser->employeeInformation->back_id_image));
            }

            $back = $request->file('back_id_image');
            $backName = time() . '_back_' . $back->getClientOriginalName();
            $back->move(public_path('images/back'), $backName);
            $employeeInfo['back_id_image'] = "images/back/$backName";
        }

        if (!$employeeUser->employeeInformation) {
            return response()->json(['error' => 'لا توجد بيانات وظيفية لهذا الموظف'], 404);

        }else{
            $employeeUser->employeeInformation->update($employeeInfo);
        }

        return response()->json([
            'message' =>trans('messages.operation_success'),
            'data' => $employeeUser,
        ]);
    }



    public function searchUsers(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'role_id' => 'required|exists:roles,id',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $name = $request->input('name');
        $roleId = $request->input('role_id');
        $fatherName = $request->input('father_name');
        $motherName = $request->input('mother_name');

        if ($roleId == 2 && (!empty($fatherName) || !empty($motherName))) {
            return response()->json([
                'message' => __('messages.can_not'),
            ], 404);
        }

        if (empty($name) && empty($fatherName) && empty($motherName)) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 400);
        }

        $query = User::query();

        if ($roleId == 2) {
            if (!empty($name)) {
                $query->where('name', 'like', '%' . $name . '%');
            }
            if (empty($name)) {
                return response()->json([
                    'message' => __('messages.name_required_for_role_2'),
                ], 400);
            }
        }

        $query->where('role_id', $roleId);

        if (in_array($roleId, [3, 4])) {
            if (!empty($name)) {
                $query->where('name', 'like', '%' . $name . '%');
            }

            if (!empty($fatherName)) {
                $query->whereHas('EmployeeInformation', function($q) use ($fatherName) {
                    $q->where('father_name', 'like', '%' . $fatherName . '%');
                });
            }

            if (!empty($motherName)) {
                $query->whereHas('EmployeeInformation', function($q) use ($motherName) {
                    $q->where('mother_name', 'like', '%' . $motherName . '%');
                });
            }
        }

        if (in_array($roleId, [3, 4])) {
            $query->with('EmployeeInformation');
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
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
            'father_name' => $user->EmployeeInformation->father_name ?? null,
            'mother_name' => $user->EmployeeInformation->mother_name ?? null,
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
        $user->tokens()->delete();

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

    public function getOwnedProperties(Request $request)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $properties = Property_for_sale::where('status', 'تم تملك العقار')
            ->select([
                'id',
                DB::raw("CONCAT(state, ' - ', exact_position) as location"),
                'property_type'
            ])
            ->paginate(5);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $properties->items(),
                'pagination' => [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                    'next_page_url' => $properties->nextPageUrl(),
                    'prev_page_url' => $properties->previousPageUrl(),
                ]
            ]
        ]);
    }

    public function getSoldProperties(Request $request)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $properties = Property_for_sale::where('status', 'تم البيع')
            ->select([
                'id',
                DB::raw("CONCAT(state, ' - ', exact_position) as location"),
                'property_type'
            ])
            ->paginate(5);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => [
                'properties' => $properties->items(),
                'pagination' => [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                    'next_page_url' => $properties->nextPageUrl(),
                    'prev_page_url' => $properties->previousPageUrl(),
                ]
            ]
        ]);
    }

    public function countUsersByRole()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $roles = [
            1 => 'admin',
            2 => 'investor',
            3 => 'economic team',
            4 => 'legal team',
        ];

        $userCounts = [];

        foreach ($roles as $id => $type) {
            $count = User::where('role_id', $id)->count();
            $userCounts[$type] = $count;
        }

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $userCounts,
        ]);
    }

    public function getLatestEconomicEvaluation($propertyForSaleId)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $economicEvaluation = EconomicEvaluation::where('property_for_sale_id', $propertyForSaleId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$economicEvaluation) {
            return response()->json([
                'message' => __('messages.not_found'),
            ], 404);
        }
        $agreement = $economicEvaluation->agreed_negotiation()->first();

        return response()->json([
            'message' => __('messages.operation_success'),
            'data' => [
                'economic_evaluation' => $economicEvaluation,
                'agreement' => $agreement,
            ],
        ], 200);
    }

}
