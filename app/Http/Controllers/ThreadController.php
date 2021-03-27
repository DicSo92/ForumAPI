<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Fractalistic\Fractal;
use Illuminate\Support\Facades\Validator;

class ThreadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $threads = Thread::with('user')
            ->with('user')
            ->with('replies.user')
            ->with('replies.thread')
            ->with('channel')
            ->get();

        $fractal =  Fractal::create()->collection($threads)
            ->transformWith(function($allThreads) {
                $user = (object)[
                    "data" => $allThreads['user']->only('name', 'email')
                ];
                $replies = (object)[
                    "data" => $allThreads['replies']
                ];
                $channel = (object)[
                    "data" => $allThreads['channel']
                ];

                return
                    [
                        'id' => $allThreads['id'],
                        'title' => $allThreads['title'],
                        'slug' => $allThreads['slug'],
                        'body' => $allThreads['body'],
                        'user' =>$user,
                        'replies'=> $replies,
                        'channel' => $channel
                    ];
            });

        return response()->json($fractal);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $credentials = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'body' => 'required',
            'channel_id' => 'required|integer|exists:channels,id',
        ]);

        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $thread = new Thread();
        $thread->title = $request->title;
        $thread->slug = Str::slug($request->title);
        $thread->body = $request->body;
        $thread->channel_id = $request->channel_id;
        $thread->user_id = Auth::id();
        $thread->save();

        return response()->json(['data'=> $thread],201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Thread  $thread
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Thread $thread)
    {
        $searchedThread = Thread::with('user')
            ->with('replies.user')
            ->with('replies.thread')
            ->with('replies.thread.user')
            ->with('channel')
            ->findOrFail($thread->id);

        return response()->json($this->structThread($searchedThread), 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Thread $thread)
    {
        if ($thread->user_id !== Auth::id()) {
            return response()->json(["errors" => 'Forbidden'], 403);
        }
        $credentials = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'slug' => 'required|max:255',
            'body' => 'required',
            'channel_id' => 'required|integer|exists:channels,id',
        ]);
        if ($credentials->fails()) {
            return response()->json(["errors" => $credentials->messages()], 422);
        }

        $thread->update([
            'title' => $request->title,
            'slug' => Str::slug($request->slug),
            'body' => $request->body,
            'channel_id' => $request->channel_id,
        ]);

        $resThread = Thread::where('id', $thread->id)
            ->with('user')
            ->with('replies.user')
            ->with('replies.thread')
            ->with('replies.thread.user')
            ->with('channel')
            ->get();

        return response()->json($this->structThread($resThread[0]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Thread $thread)
    {
        if(!$thread){
            return response()->json(["errors" => []],404);
        };
        if($thread->user_id != Auth::id()){
            return response()->json(["errors" => []],403);
        }

        $thread->delete();

        return response()->json([],204);
    }

    protected function structThread($thread) {
        return Fractal::create()->item($thread)->transformWith(function($sThread) {
            $replies = Fractal::create()->collection($sThread['replies'])->transformWith(function($reply)
            {
                return [
                    'id' => $reply['id'],
                    'created_at' => $reply['created_at'],
                    'updated_at' => $reply['updated_at'],
                    'body' => $reply['body'],
                    'user' => (object)[
                        "data" => $reply['user']
                    ],
                    "thread" => (object)[
                        "data" => $reply['thread']
                    ]
                ];
            });

            $channel = (object)[
                "data" => $sThread['channel']
            ];

            return [
                'data' => (object)[
                    'id' => $sThread['id'],
                    'title' => $sThread['title'],
                    'slug' => $sThread['slug'],
                    'body' => $sThread['body'],
                    'user' =>$sThread['user'],
                ],
                'channel' => $channel,
                'replies'=> $replies
            ];
        });
    }
}
