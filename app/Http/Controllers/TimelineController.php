<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Experience;
use App\Models\Skill;
use Carbon\Carbon;
use Google\Service\CloudSourceRepositories\Repo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimelineController extends Controller
{
    private $apiResponse;

    public function __construct()
    {
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Display posts on timeline
     * Order by timeline_orders and id, DESC
     * @param \Illuminate\Http\Request $request
     * @return string|mixed String JSON for REST API
     */
    public function timeline(Request $request)
    {
        $param = $request->all();

        // Fetch posts with related author information.
        $posts = Post::with(
            [
                // Load author information (id, name, avatar).
                'author' => function ($authorQuery) {
                    return $authorQuery->select('id', 'name', 'avatar', 'email', 'location');
                },
                // Load the author's experience.
                'author.experiences' => function ($authorExpQuery) {
                    return $authorExpQuery->orderBy('id', 'DESC')->get();
                },
                // Load author's skills
                'author.skills' => function ($authorSkillQuery) {
                    return $authorSkillQuery->select('id', 'skill', 'user_id');
                },
                // Load likes and comments
                'likes',
                'comments',
                // Load the favorites for the current authenticated user related to the post.
                'favorites' => function ($favoriteQuery) {
                    return $favoriteQuery->where('user_id', Auth::user()->id)->get();
                }
            ]
        )
            // Select the necessary columns from the posts table.
            ->select(
                'posts.id',
                'posts.content',
                'posts.timeline_orders',
                'posts.view_count',
                'posts.images',
                'posts.user_id',
                'posts.created_at'
                // 'users.id as author_id',
                // 'users.name as author_name',
                // 'users.avatar as author_avatar',
                // 'users.location'

            )
            // Order the posts by timeline_orders (descending) and then by id (descending).
            ->orderBy('posts.timeline_orders', 'DESC')
            ->orderBy('posts.id', 'DESC')
            // Apply pagination using offset and limit.
            ->skip($param['offset'])
            ->take($param['limit'])
            // Retrieve the posts.
            ->get();

        // Return HTTP 204 if no data found
        if (empty($posts)) {
            return $this->apiResponse->dataNotfound();
        }

        // Loop through each post.
        foreach ($posts as $post) {
            // Assign the title of the author's first experience to the post.
            // If the author has no experiences, assign an empty string.
            $post->experiences = $post->author->experiences[0]->title ?? "";
            // Assign the author's skills to the post.
            $post->skills = $post->author->skills;
            // Count the number of likes and comments for the current post.
            $post->total_like = count($post->likes);
            $post->total_comment = count(value: $post->comments);

            // Initialize a flag to indicate whether the current user has liked the post.
            $isLike = Post::UN_LIKE;
            // Check if the post has any likes.
            if (count($post->likes) > 0) {
                foreach ($post->likes as $like) {
                    // Check if the currently authenticated user's ID matches the user ID of the like.
                    if (Auth::user()->id == $like->user_id) {
                        // If the user has liked the post, set the flag to 1.
                        $isLike = Post::LIKE;
                        break;
                    }
                }
            }
            // Assign the value of $isLike to is_like attributes of post object
            $post->is_like = $isLike;

            // Truncate the post content to a maximum of 100 characters for display purposes.
            $shortContent = null;
            if (strlen($post->content) > 100) {
                $shortContent = mb_substr(
                    $post->content,
                    0,
                    99,
                    "UTF-8"
                );
            }
            $post->short_content = $shortContent;

            // Check if the author has an avatar.
            if (!is_null($post->author->avatar)) {
                $avatarTmp = $post->author->avatar;
                // Construct the full path to the avatar image.
                // Assumes avatars are stored in a directory named after the username (part before the @ in the email) within the 'public/avatars' directory.
                // The filename is the value stored in the database.
                $post->author->_avatar =
                    env('APP_URL') . '/avatars/'
                    . explode('@', $post->author->email)[0] . '/'
                    . $avatarTmp;
            } else {
                $post->author->_avatar = null;
            }

            // Set the Carbon library's locale to the application's configured locale.
            Carbon::setLocale(config('app.locale'));

            // Calculate the human-readable difference between the post's creation time and the current time.
            // This will be used to display how long ago the post was created (e.g., "2 hours ago").
            $post->since_created = $post->created_at->diffForHumans(Carbon::now());

            // Unset the original instances from the author object to clean up the response.
            unset(
                $post->author->experiences,
                $post->author->skills,
                $post->likes,
                $post->comments,
                $post->created_at
            );
        }

        // Return the posts in a successful API response.
        return $this->apiResponse->success($posts);
    }

    /**
     * Add a favorite for a post
     *
     * This method handles the addition of a favorite for a post by a user.
     * It accepts a request containing the post_id and performs the following:
     * - Retrieves the current timestamp.
     * - Inserts a new record into the 'favorites' table with the user_id, post_id, and timestamps.
     * - Returns a success response with the post_id if the operation is successful.
     * - Handles exceptions and returns an internal server error response if something goes wrong.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return string|mixed JSON response indicating success or error.
     */
    public function addFavorite(Request $request)
    {
        // Retrieve all request parameters
        $param = $request->all();
        try {
            // Get the current timestamp
            $now = Carbon::now();
            // Insert a new favorite record into the 'favorites' table
            DB::table('favorites')->insert([
                'user_id' => Auth::user()->id,  // Get the ID of the currently authenticated user
                'post_id' => $param['post_id'], // Get the post_id from the request parameters
                'created_at' => $now,           // Use the current timestamp for the created_at field
                'updated_at' => $now,           // Use the current timestamp for the updated_at field
            ]);
            // Return a success response with the post_id
            return $this->apiResponse->success($param['post_id']);
        } catch (\Exception $e) {
            // Return an internal server error response
            return $this->apiResponse->InternalServerError();
        }
    }

    /**
     * Remove a favorite for a post
     *
     * This method handles the removal of a favorite for a post by a user.
     * It accepts a request containing the post_id and performs the following:
     * - Extracts the post_id from the request parameters.
     * - Deletes the record from the 'favorites' table where the user_id matches the currently authenticated user and the post_id matches the provided post_id.
     * - Returns a success response with the post_id if the operation is successful.
     * - Handles exceptions and returns an internal server error response if something goes wrong.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return string|mixed JSON response indicating success or error.
     */
    public function removeFavorite(Request $request)
    {
        // Retrieve all request parameters
        $param = $request->all();

        try {
            // Delete the favorite record from the 'favorites' table
            DB::table('favorites')
                ->where('user_id', Auth::user()->id) // Filter by the currently authenticated user's ID
                ->where('post_id', $param['post_id']) // Filter by the provided post_id
                ->delete(); // Perform the delete operation
            // Return a success response with the post_id
            return $this->apiResponse->success($param['post_id']);
        } catch (\Exception $e) {
            // Return an internal server error response if an exception occurs
            return $this->apiResponse->InternalServerError();
        }
    }

    /**
     * Handle liking or unliking a post.
     *
     * This method accepts a request containing 'post_id' and 'action' ('like' or 'unlike').
     * Based on the 'action', it either inserts a new like record or deletes an existing one.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return string|mixed JSON response indicating success or error.
     */
    public function like(Request $request)
    {
        // Retrieve request parameters (including post_id and action)
        $param = $request->all();

        try {
            // Get the current timestamp
            $now = Carbon::now();

            // Check the action parameter
            if ($param['action'] == 'like') {
                // Action is 'like': Insert a new record into the 'likes' table
                DB::table('likes')->insert([
                    'user_id' => Auth::user()->id,
                    'post_id' => $param['post_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                // Action is 'unlike': Delete the record from the 'likes' table
                DB::table('likes')
                    ->where('user_id', Auth::user()->id) // Filter by the user ID
                    ->where('post_id', $param['post_id']) // Filter by the post ID
                    ->delete();
            }

            // Return a success response with the post_id
            return $this->apiResponse->success($param['post_id']);
        } catch (\Exception $e) {
            // Return an internal server error response if an exception occurs
            return $this->apiResponse->InternalServerError();
        }
    }

    /**
     * List comments for a specific post.
     *
     * Retrieves paginated comments ordered by ID descending for a given post.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request containing post_id, offset, and limit.
     * @return string|mixed JSON response with comments or an empty array.
     */
    public function listComment(Request $request)
    {
        // Retrieve request parameters (post_id, offset, limit)
        $param = $request->all();

        // Fetch comments for the given post_id, joining with users table to get author details.
        // Order by comment ID descending and apply pagination.
        $comments = Comment::join(
            'users', // Join the 'comments' table with the 'users' table
            'comments.user_id', // on the user_id column from comments
            'users.id' // and the id column from users
        )
            // Load relationship 'child'
            ->with('child', 'child.author')
            ->select(
                // Select necessary columns, aliasing comment ID for clarity
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.avatar',
                'comments.id',
                'comments.comment',
                'comments.post_id',
                'comments.parent_id',
                'comments.created_at',
            )
            // Filter to get only top-level comments (comments with no parent)
            ->where('comments.parent_id', 0)
            // Filter comments belonging to the specific post ID from the request
            ->where('comments.post_id', $param['post_id'])
            // Order the results by comment ID in descending order
            ->orderBy('comments.id', 'DESC')
            // Skip a number of results for pagination (offset)
            ->skip($param['offset'])
            // Limit the number of results fetched for pagination (limit)
            ->take($param['limit'])
            // Execute the query and get the results
            ->get();

        // Check if there are comments found
        if (count($comments) > 0) {
            // Loop through each top-level comment
            foreach ($comments as $comment) {

                // Generate avatar URL for the top-level comment author if available
                if (!is_null($comment->avatar)) {
                    $avatarTmp = $comment->avatar;
                    $comment->_avatar = env('APP_URL')
                        . '/avatars/'
                        . explode('@', $comment->email)[0]
                        . '/'
                        . $avatarTmp;
                } else {
                    $comments->_avatar = null;
                }

                // Check if the comment has any replies (child comments)
                if (count($comment->child) > 0) {
                    // Loop through each child comment
                    foreach ($comment->child as $cmtChild) {
                        // Generate avatar URL for the child comment author if available
                        if (!is_null($cmtChild->author->avatar)) {
                            $avatarTmp = $cmtChild->author->avatar;
                            $cmtChild->author->_avatar = env('APP_URL')
                                . 'avatars/'
                                . explode('@', $cmtChild->author->email)[0]
                                . '/'
                                . $avatarTmp;
                        } else {
                            $cmtChild->author->_avatar = null;
                        }

                        $created_at_tmp_child = Carbon::create($cmtChild->created_at);
                        $cmtChild->_created_at = $created_at_tmp_child->format('Y-m-d h:i');
                    }
                }

                $created_at_tmp = Carbon::create($comment->created_at);
                $comment->_created_at = $created_at_tmp->format('Y-m-d h:i');
            }
        }

        // Return a success response with the fetched comments
        return $this->apiResponse->success($comments);
    }

    /**
     * Post a new comment or reply to a post.
     *
     * Creates a new comment record in the database based on the provided request data.
     * The request should include post_id, comment content, and parent_id (0 for top-level comments).
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request with comment data.
     * @return string|mixed JSON response indicating success or error.
     */
    public function postComment(Request $request)
    {
        $param = $request->all();
        $comment = new Comment();
        $comment->user_id = Auth::user()->id;
        $comment->post_id = $param['post_id'];
        $comment->comment = $param['comment'];
        $comment->parent_id = $param['parent'];
        $comment->save();

        $avatar = Auth::user()->avatar;
        $avatar = null;
        if (!is_null(Auth::user()->avatar)) {
            $avatarTmp = Auth::user()->avatar;
            $avatar = env('APP_URL') . 'avatars/'
                . explode('@', Auth::user()->email)[0] . '/'
                . $avatarTmp;
        }
        $responseData = [
            'id' => $comment->id,
            'comment' => $param['comment'],
            'avatar' => $avatar,
            'name' => Auth::user()->name,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'parent_id' => $param['parent'],
            'child' => null,
            'post_id' => $param['post_id'],
            'type' => 'comment',
            'action' => 'send_comment',
            'author' => [
                'name' => Auth::user()->name,
                '_avatar' => $avatar,
            ],
            '_created_at' => Carbon::now()
        ];

        return $this->apiResponse->success($responseData);
    }
}
