<?php

namespace App\Http\Controllers\v1;


use App\Models\Help;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HelpController extends BaseController
{
    public function addHelp(Request $request)
    {

        $userRole = auth()->user()->role_id;
        if ($userRole !== 2 && $userRole !== 4) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(),[
            'question' => 'required|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $help = Help::create([
            'question' => $request->question,
            'Answer' => null,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $help
        ]);
    }

    public function addAnswer(Request $request, $id)
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(),[
            'Answer' => 'required|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }
        $help = Help::find($id);

        if (!$help) {
            return response()->json([
                'message' => trans('messages.not_found')
            ], 404);
        }

        $help->Answer = $request->Answer;
        $help->save();

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $help
        ]);
    }

    public function getQuestions()
    {
        $userRole = auth()->user()->role_id;
        if ($userRole !== 1 ) {
            return response()->json([
                'message' => trans('messages.unauthorized'),
            ], 403);
        }
        $questions = Help::with('user:id,name')->get()->map(function ($question) {
            return [
                'id' => $question->id,
                'question' => $question->question,
                'Answer' => $question->Answer,
                'created_at' => $question->created_at->format('Y-m-d'),
                'user_name' => $question->user->name ?? null,
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $questions
        ]);
    }

    public function getMyQuestions()
    {

        $user = Auth::user();

        $questions = Help::where('user_id', $user->id)->with('user:id,name')->get()->map(function ($question) {
            return [
                'id' => $question->id,
                'question' => $question->question,
                'Answer' => $question->Answer,
                'created_at' => $question->created_at->format('Y-m-d'),
            ];
        });

        return response()->json([
            'message' => trans('messages.operation_success'),
            'data' => $questions
        ]);
    }

    public function deleteQuestion($id)
    {

        $question = Help::find($id);

        if (!$question) {
            return response()->json([
                'message' => trans('messages.not_found')
            ], 404);
        }

        $question->delete();

        return response()->json([
            'message' => trans('messages.operation_success')
        ]);
    }

}
