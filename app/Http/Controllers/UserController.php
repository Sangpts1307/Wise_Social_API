<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\SendMail;
use App\Models\DeviceToken;
use App\Models\Follow;
use App\Models\Friend;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Google\Service\AndroidEnterprise\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * 
     */
    public function show(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();
        // Return unauthorized response if no user is authenticated
        if (!$userId) {
            return $this->apiResponse->UnAuthorization();
        };

        // Get user with follower and follows relationships, selecting specific fields
        $user = User::with('follower', 'follows')
            ->select('id', 'name', 'email', 'avatar', 'overview')
            ->where('id', $userId)->first();

        // Count followers and following
        $user->followers = count($user->follower);
        $user->following = count($user->follows);
        // Remove relationship data after counting
        unset($user->follower, $user->follows);

        // Initialize avatar folder variable
        $folderAvatar = null;
        // If user has avatar, generate full avatar URL
        if (!is_null($user->avatar)) {
            $folderAvatar = explode('@', $user->email);
            $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
        }

        // Return success response with user data
        return $this->apiResponse->success($user);
    }

    /**
     * Controller for suggesting friends
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function suggestFriend(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Get array of friend IDs from friends table for current user
        $listFriendIds = DB::table('friends')
            ->where('user_id', $userId)
            ->select('friend_id')
            ->pluck('friend_id')
            ->toArray();

        // Add current user ID to exclude from suggestions
        $listFriendIds[] = $userId;

        // Query users table for suggested friends
        $suggest = User::with([
            // Load experiences relationship with selected fields
            'experiences' => function ($experienceQuery) {
                return $experienceQuery->select('id', 'user_id', 'title');
            }
        ])
            ->whereNotIn('id', $listFriendIds) // Exclude current friends and self
            ->where('status', User::STATUS_ACTIVE) // Only active users
            ->select('id', 'name', 'avatar', 'created_at') // Select needed fields
            ->orderBy('created_at', 'ASC') // Sort by join date
            ->limit(config('constant.limit')) // Limit results
            ->get();

        // Process each suggested user if any found
        if (count($suggest) > 0) {
            foreach ($suggest as $user) {
                // Initialize avatar folder variable
                $folderAvatar = null;

                // Generate full avatar URL if user has avatar
                if (!is_null($user->avatar)) {
                    $folderAvatar = explode('@', $user->email);
                    $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
                }

                // Initialize experience text and counter
                $txtExperience = '';
                $i = 1;

                // Build comma-separated list of experience titles
                foreach ($user->experiences as $experience) {
                    if ($i < count($user->experiences)) {
                        $txtExperience .= $experience->title . ', ';
                    } else {
                        $txtExperience .= $experience->title;
                    }
                    $i++;
                }

                // Replace experiences array with formatted string
                $user->experience = $this->truncateString($txtExperience, 20);
                unset($user->experiences);
            }
        }

        // Return success response with suggested friends
        return $this->apiResponse->success($suggest);
    }
    /**
     * Truncates a string to a specified length, appending a suffix if truncated.
     *
     * @param string $string The string to truncate.
     * @param int $length The maximum length of the string.
     * @param string $append The suffix to append if the string is truncated (default: '...').
     * @return string The truncated string.
     */
    private function truncateString($string, $length, $append = '...')
    {
        // Check if the string's length exceeds the specified length.
        if (mb_strlen($string) > $length) {
            // If it does, truncate the string to the specified length and append the suffix.
            return mb_substr($string, 0, $length) . $append;
        }
        // If the string's length is within the limit, return the original string.
        return $string;
    }

    /**
     * Controller for Add Friend function
     * @param \Illuminate\Http\Request $request
     * @return bool|string
     */
    public function addFriend(Request $request)
    {
        // Get all request parameters
        $param = $request->all();

        try {
            // Start database transaction
            DB::beginTransaction();

            // Create new friend request record
            $friend = new Friend();
            $friend->user_id = Auth::id(); // Current authenticated user
            $friend->friend_id = $param['friend_id']; // Target friend ID
            $friend->approved = Friend::UNAPPROVED; // Set initial unapproved status
            $friend->created_at = Carbon::now();
            $friend->save();

            // Commit transaction if successful
            DB::commit();

            // Send notify email
            $receivUser = User::find($param['friend_id']);
            $sendMail = new SendMail();
            $sendMail->sendMail003($receivUser, Auth::user());

            // Add notification to queue
            $notification = new Notification();
            $notification->user_id = $param['friend_id'];
            $notification->actor_id = Auth::id();
            $notification->content = Auth::user()->name . " sent you a friend request.";
            $notification->is_view = Notification::UNVIEW;
            $notification->status = Notification::STATUS_WAIT;
            $notification->save();

            return $this->apiResponse->success();
        } catch (\Exception $e) {
            // Rollback transaction and log error if failed
            DB::rollback();
            Log::error($e->getMessage());

            return $this->apiResponse->InternalServerError();
        }
    }

    public function listFriendRequest(Request $request)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Query users table for suggested friends
        $requests = User::with([
            // Load experiences relationship with selected fields
            'experiences' => function ($experienceQuery) {
                return $experienceQuery->select('id', 'user_id', 'title');
            }
        ])
            ->join('friends', 'users.id', 'friends.user_id')
            ->where('friend_id', $userId) // Exclude current friends and self
            ->where('status', User::STATUS_ACTIVE) // Only active users
            ->where('friends.approved', Friend::UNAPPROVED)
            ->select('friends.id', 'users.email', 'users.name', 'users.avatar', 'users.created_at') // Select needed fields
            ->orderBy('friends.created_at', 'ASC') // Sort by join date
            ->limit(config('constant.limit')) // Limit results
            ->get();

        // dd($requests);

        // Process each suggested user if any found
        if (count($requests) > 0) {
            foreach ($requests as $user) {
                // Initialize avatar folder variable
                $folderAvatar = null;

                // Generate full avatar URL if user has avatar
                if (!is_null($user->avatar)) {
                    $folderAvatar = explode('@', $user->email);
                    $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
                }

                // Initialize experience text and counter
                $txtExperience = '';
                $i = 1;

                // Build comma-separated list of experience titles
                foreach ($user->experiences as $experience) {
                    if ($i < count($user->experiences)) {
                        $txtExperience .= $experience->title . ', ';
                    } else {
                        $txtExperience .= $experience->title;
                    }
                    $i++;
                }

                // Replace long string with truncated string
                $user->experience = $this->truncateString($txtExperience, length: 15);
                $user->name = $this->truncateString($user->name, length: 15);
                unset($user->experiences);
            }
        }

        // Return success response with suggested friends
        return $this->apiResponse->success($requests);
    }

    /**
     * Accept or dismiss a friend request
     * Updates friend status to approved if accepted, deletes record if dismissed
     * 
     */
    public function accept(Request $request)
    {
        $param = $request->all();

        if ($param['type'] == 'accept') {
            // Approve friend request by updating status
            return DB::table('friends')
                ->where('id', $param['id'])
                ->update([
                    'approved' => Friend::APPROVED
                ]);
        } else {
            // Delete friend request if dismissed
            return DB::table('friends')
                ->where('id', $param['id'])
                ->delete();
        }
    }

    /**
     * Most followed
     * 
     */
    public function mostFollowed(Request $request)
    {
        // Get most followed user with their basic info and follow count
        $user = User::select(
            'users.id',
            'users.name',
            'users.email',
            'users.avatar',
            DB::raw('COUNT(follows.id) as total_follow'),
            'follows.follow_id'
        )->join('follows', 'users.id', 'follows.follow_id')
            ->with([
                'experiences' => function ($experienceQuery) {
                    return $experienceQuery->select('id', 'user_id', 'title');
                }
            ])->groupBy(
                'follows.follow_id',
                'users.id',
                'users.name',
                'users.email',
                'users.avatar'
            )->orderBy('total_follow', 'DESC')->first();

        // Build full avatar URL if user has avatar
        $folderAvatar = null;
        if (!is_null($user->avatar)) {
            $folderAvatar = explode('@', $user->email);
            $user->avatar = url(
                'avatars/' . $folderAvatar[0] . '/' . $user->avatar
            );
        }

        // Build comma-separated list of experiences
        $txtExperience = '';
        $i = 1;
        foreach ($user->experiences as $experience) {
            if ($i < count($user->experiences)) {
                $txtExperience .= $experience->title . ', ';
            } else {
                $txtExperience .= $experience->title;
            }
            $i++;
        }

        // Truncate long strings and clean up response
        $user->experience = $this->truncateString($txtExperience, 15);
        $user->name = $this->truncateString($user->name, 10);
        unset($user->experiences);
        return $this->apiResponse->success($user);
    }

    public function search(Request $request)
    {
        // Get all request parameters
        $param = $request->all();

        // Query users with their experiences, filtering by name or email
        $users = User::with([
            // Load experiences relationship with only necessary fields
            'experiences' => function ($experienceQuery) {
                return $experienceQuery->select('id', 'user_id', 'title');
            }
        ])
            // Select only necessary user fields
            ->select('id', 'name', 'email', 'avatar')
            // Filter users who have experiences matching the search keyword
            ->whereHas('experiences', function ($query) use ($param) {
                return $query->where('title', 'Like', '%' . $param['key-word'] . '%');
            })
            // Search users where name or email starts with search keyword
            ->orWhere('name', 'Like', '%' . $param['key-word'] . '%')
            ->orWhere('email', 'Like', '%' .  $param['key-word'] . '%')
            // Sort results by newest first
            ->orderBy('id', "DESC")
            ->get();

        if (count($users) > 0) {
            foreach ($users as $user) {
                // Initialize variable to store email prefix for avatar path
                $folderAvatar = null;

                // If user has an avatar, construct the full URL
                // Avatar is stored in a folder named after user's email prefix
                if (!is_null($user->avatar)) {
                    $folderAvatar = explode('@', $user->email);
                    $user->avatar = url('avatars/' . $folderAvatar[0] . '/' . $user->avatar);
                }

                // Initialize variables for formatting experience list
                $txtExperience = '';
                $i = 1;

                // Create a comma-separated string of user's experience titles
                // Last experience title doesn't get a comma
                foreach ($user->experiences as $experience) {
                    if ($i < count($user->experiences)) {
                        $txtExperience .= $experience->title . ', ';
                    } else {
                        $txtExperience .= $experience->title;
                    }
                    $i++;
                }

                // Convert experiences to truncated string and remove original array
                // Limit experience string to 20 characters to keep response concise
                $user->experience = $this->truncateString($txtExperience, 100);
                $user->name = $this->truncateString($user->name, 100);
                unset($user->experiences);
            }
        }
        return $this->apiResponse->success($users);
    }

    public function setDeviceToken(Request $request)
    {
        $param = $request->all();
        $userId = Auth::user()->id;

        // Check if token already exists
        $checkToken = DeviceToken::where('user_id', $userId)->get();

        if (count($checkToken) == 0) {
            $deviceToken = new DeviceToken();
            $deviceToken->user_id = Auth::user()->id;
            $deviceToken->token = $param['fcmToken'];
            $deviceToken->save();
        } else {
            $deviceToken = DeviceToken::where('user_id', $userId)->first();
            $deviceToken->token = $param['fcmToken'];
            $deviceToken->updated_at = Carbon::now();
            $deviceToken->update();
        }

        return $this->apiResponse->success($deviceToken);
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
