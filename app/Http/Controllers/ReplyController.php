<?php

namespace App\Http\Controllers;

use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Fractalistic\Fractal;

class ReplyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Thread       $thread
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, Thread $thread)
    {
        $credentials = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $reply = new Reply();
        $reply->body = $request->body;
        $reply->thread_id = $thread->id;
        $reply->user_id = Auth::id();
        $reply->save();

        return response()->json($this->structReply($reply),201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Thread  $thread
     * @param  \App\Models\Reply  $reply
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Thread $thread, Reply $reply)
    {
        if ($reply->user_id !== Auth::id()) {
            return response()->json(["errors" => 'Forbidden'], 403);
        }
        $searchedReply = Reply::with('user')
            ->with('thread')
            ->with('thread.user')
            ->findOrFail($reply->id);

        return response()->json($this->structReply($searchedReply), 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Thread       $thread
     * @param \App\Models\Reply        $reply
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Thread $thread, Reply $reply)
    {
        if ($reply->user_id !== Auth::id()) {
            return response()->json(["errors" => 'Forbidden'], 403);
        }
        if ($reply->thread_id !== $thread->id) {
            return response()->json(["errors" => []], 422);
        }
        $credentials = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);
        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $reply->update([
            'body' => $request->body
        ]);

        $mReply = Reply::where('id', $reply->id)
            ->with('user')
            ->with('thread.user')
            ->get();

        $resReply = Fractal::create()->item($mReply[0])->transformWith(function($sReply) {
            return [
                'id' => $sReply['id'],
                'created_at' => $sReply['created_at'],
                'updated_at' => $sReply['updated_at'],
                'body' => $sReply['body'],
                'user' => (object)[
                    "data" => $sReply['user']
                ],
                "thread" => (object)[
                    "data" => $sReply['thread']
                ]
            ];
        });

        return response()->json($resReply, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Thread $thread
     * @param \App\Models\Reply  $reply
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Thread $thread, Reply $reply)
    {
        if(!$reply){
            return response()->json(["errors" => []],404);
        };
        if($reply->user_id != Auth::id()){
            return response()->json(["errors" => []],403);
        }

        $reply->delete();

        return response()->json([],204);
    }

    protected function structReply($reply) {
        return Fractal::create()->item($reply)
            ->transformWith(function($replies) {
                return [
                    'id' => $replies['id'],
                    'created_at' => $replies['created_at'],
                    'updated_at' => $replies['updated_at'],
                    'body' => $replies['body'],
                    'user' => (object)[
                        "data" => $replies['user']
                    ],
                    "thread" => (object)[
                        "data" => $replies['thread']
                    ]
                ];
            });
    }
}
