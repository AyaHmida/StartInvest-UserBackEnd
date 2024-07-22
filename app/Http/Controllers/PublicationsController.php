<?php

namespace App\Http\Controllers;

use App\Events\MyEvent;
use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Publication;
use App\Models\User;
use App\Notifications\LikedDBNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class PublicationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $authenticatedUser = Auth::user();
        if ($authenticatedUser) {
            $publications = Publication::where('user_id', '!=', $authenticatedUser->id)
                ->with('user')
                ->get();
            foreach ($publications as $publication) {
                $likes = Like::where('post_id', $publication->id)
                    ->where('like', 1)
                    ->with('user')
                    ->get();

                $likesCount = $likes->count();
                $publication->countLikes = $likesCount;
                $publication->likes = $likes;
            }

            $data = [
                'status' => 200,
                'publications' => $publications,
            ];
            return response()->json($data, 200);
        } else {
            $errorData = [
                'status' => 401,
                'message' => 'user not authentificated',
            ];
            return response()->json($errorData, 401);

        }

    }
    public function userProfilePublications()
    {
        try {
            $authenticatedUser = Auth::user();

            $publications = Publication::where('user_id', $authenticatedUser->id)
                ->with('user')
                ->get();
            foreach ($publications as $publication) {
                $likes = Like::where('post_id', $publication->id)
                    ->where('like', 1)
                    ->with('user')
                    ->get();

                $likesCount = $likes->count();
                $publication->countLikes = $likesCount;
                $publication->likes = $likes;
            }

            $data = [
                'status' => 200,
                'publications' => $publications,
            ];
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $errorData = [
                'status' => 500,
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ];
            return response()->json($errorData, 500);
        }
    }
    public function userProfilePublicationsId($userId)
    {
        try {
            // $authenticatedUser = Auth::user();

            // Récupérer les publications de l'utilisateur spécifié par son ID
            $publications = Publication::where('user_id', $userId)
                ->with('user')
                ->get();

            foreach ($publications as $publication) {
                $likes = Like::where('post_id', $publication->id)
                    ->where('like', 1)
                    ->with('user')
                    ->get();

                $likesCount = $likes->count();
                $publication->countLikes = $likesCount;
                $publication->likes = $likes;
            }

            $data = [
                'status' => 200,
                'publications' => $publications,
            ];
            return response()->json($data, 200);
        } catch (\Exception $e) {
            $errorData = [
                'status' => 500,
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ];
            return response()->json($errorData, 500);
        }
    }

    public function store(Request $request)
    {
        if (Auth::check()) {
            $validator = Validator::make($request->all(), [
                'description' => 'required|string',
                // 'file' => 'nullable|file|mimes:jpg,png,jpeg,mp4,mov,avi|max:20480',
            ]);

            if ($validator->fails()) {
                $data = [
                    'status' => 422,
                    'message' => $validator->messages(),
                ];
                return response()->json($data, 422);
            } else {
                $authenticatedUser = Auth::user();

                $filename = null;

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $filename = time() . '.' . $file->getClientOriginalExtension();
                    $path = 'uploads/';
                    $file->move($path, $filename);
                }

                $publication = new Publication();
                $publication->description = $request->description;
                $publication->file = $filename;
                $publication->user_id = $authenticatedUser->id;
                $publication->save();

                event(new MyEvent($publication));

                $data = [
                    'status' => 200,
                    'message' => 'Données crees avec succes',
                ];
                return response()->json($data, 200);
            }
        } else {
            $data = [
                'status' => 401,
                'message' => 'Utilisateur non authentifie',
            ];
            return response()->json($data, 401);
        }
    }

    public function destroy(string $id)
    {
        $publication = Publication::find($id);
        $publication->delete();
        $data = [
            'status' => 200,
            'publications' => 'publication deleted with success',
        ];
        return response()->json($data, 200);
    }
    public function like($id)
    {
        $post_id = $id;
        $user_id = Auth::user()->id;

        $existingLike = Like::where('post_id', $post_id)
            ->where('user_id', $user_id)
            ->first();

        if ($existingLike) {
            if ($existingLike->like === 1) {
                return response()->json('You have already liked this post.');
            } else {
                $existingLike->like = 1;
                $existingLike->save();
                return response()->json('You disliked this post previously but now you liked it.');
            }
        } else {
            $like = new Like();
            $like->post_id = $post_id;
            $like->user_id = $user_id;
            $like->like = 1;
            $like->save();
            $operation = 'liked';

            $utilisateursANotifier = User::where('id', '!=', Auth::id())->get();
            Notification::send($utilisateursANotifier, new LikedDBNotify($like, $operation));

            return response()->json('You liked this post.');
        }
    }
    public function dislike($id)
    {
        $post_id = $id;
        $user_id = Auth::user()->id;

        $like = Like::where('post_id', $post_id)
            ->where('user_id', $user_id)
            ->first();

        if ($like) {
            $like->update(['like' => 0]);
            return response()->json('you have disliked this post');
        } else {
            return response()->json('you have not liked this post before');
        }
    }

}
