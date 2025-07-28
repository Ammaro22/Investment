<?php

namespace App\Http\Controllers\v1;

use App\Models\Frequently_question;
use App\Models\Help;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FrequentlyQuestionsController extends Controller
{

    public function create(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'question' => 'required|string|max:3000',
            'Answer' => 'required|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $question = Frequently_question::create([
            'question' => $request->question,
            'Answer' => $request->Answer,
        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $question
        ]);
    }

    public function index()
    {
        $questions = Frequently_question::all();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $questions
        ]);
    }

    public function destroy($id)
    {

        $question = Frequently_question::find($id);

        if (!$question) {
            return response()->json([
                'message' => trans('messages.not_found'),
            ], 404);
        }
        $question->delete();

        return response()->json([
            'message' => trans('messages.delete_success'),
        ]);
    }

}
