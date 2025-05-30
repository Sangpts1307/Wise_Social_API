<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use App\Models\Friend;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{

    private $apiResponse;

    public function __construct()
    {
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Retrieve all parameters from the request
        $param = $request->all();

        // Initialize variable to store uploaded file
        $file = null;
        $fileName = null;

        // Check if an image file is uploaded
        if ($request->hasFile('image')) {
            // Retrieve the uploaded file
            $file = $request->file('image');
            // Generate a unique file name based on user ID and timestamp
            $fileName = Auth::user()->id . "_" . date('ymdhis') . "." . $file->getClientOriginalExtension();
            // Create 'post_images' directory if it doesn't exist
            if (!is_dir(public_path("post_images"))) {
                mkdir(public_path("post_images"));
            }
            // Move uploaded file to 'post_images' directory
            move_uploaded_file($file, public_path('post_images') . '/' . $fileName);
        }

        // Create a new Post instance
        $post = new Post();

        // Set post attributes
        $post->user_id = Auth::user()->id; // Set post owner
        $post->content = $param['content']; // Set post content
        $post->timeline_orders = Carbon::now(); // Set post timestamp
        $post->view_count = 0; // Initialize view count
        $post->images = $fileName; // Set uploaded image file name
        // Save the post to the database
        $post->save();

        // Retrieve the user's friends
        $friends = Friend::where('user_id', Auth::user()->id)->get();

        // Check if the user has any friends
        if (count($friends) > 0) {
            // Initialize an array to store notifications for each friend
            $arrPushToFriend = [];

            // Loop through each friend and create a notification for them
            foreach ($friends as $friend) {
                // Create a notification for the friend with the post author as the actor
                $arrPushToFriend[] = [
                    'user_id' => $friend->friend_id, // The friend who will receive the notification
                    'actor_id' => Auth::user()->id, // The author of the post (the actor)
                    'content' => Auth::user()->name . " just posted a new post.", // The content of the notification
                    'is_view' => Notification::UNVIEW, // The notification is initially set to unviewed
                    'status' => Notification::STATUS_WAIT, // The notification status is set to wait
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),

                ];
            }

            // Insert the notifications into the database
            DB::table('notifications')->insert($arrPushToFriend);
        }

        // Return a successful response with the created post
        return $this->apiResponse->success($post);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
