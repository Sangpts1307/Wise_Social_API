<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ApiResponse;
use App\Mail\InvalidLoginMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;

class APIController extends Controller
{
    private $apiResponse;

    public function __construct()
    {
        $this->apiResponse = new ApiResponse();
    }

    /**
     * Controller method for api list all user
     *
     * @return bool|string JSON
     */
    public function listUser()
    {
        $users = User::select('id', 'email', 'address', 'created_at', 'role')
            ->get();
        return $this->apiResponse->success($users);
    }

    public function store(Request $request)
    {
        $param = $request->all();
        $user = new User();
        $user->status = 1;
        $user->phone = $param['phone'];
        $user->address = $param['address'];
        $user->avatar = $param['avatar'];
        $user->name = $param['name'];
        $user->email = $param['email'];
        $user->password = Hash::make($param['password']);
        $user->role = 0;
        $user->save();
        return $this->apiResponse->success($user);
    }

    public function updatePassword(Request $request)
    {
        $param = $request->all();
        $user = User::find($param['user_id']);
        if (!$user) {
            return $this->apiResponse->dataNotfound();
        }
        $user->password = Hash::make($param['new_password']);
        $user->update();
        return $this->apiResponse->success($user);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->apiResponse->dataNotfound();
        }
        $user->delete();
        return $this->apiResponse->success();
    }

    public function login(Request $request)
    {
        $param = $request->all();
        $credential = [
            'email' => $param['email'],
            'password' => $param['password']
        ];
        // Check if account already banned
        $checkInvalidLogin = User::where('email', $param['email'])->first();
        if ($checkInvalidLogin->login_fail > 6) {
            // Send error notification email if account already banned
            // logged in the 7th time
            return $this->apiResponse->UnAuthorization(Lang::get('message.auth.login_limit_exceeded'));
        }

        // Process login
        if (Auth::attempt($credential)) {
            $user = Auth::user();
            $success = $user->createToken($user->id);
            return $this->apiResponse->success($success);
        } else {
            // Count login failure time
            $user = User::where('email', $param['email'])->first();
            if ($user->login_fail <= 5) {
                $loginFail = $user->login_fail + 1;
                DB::table('users')->where('id', $user->id)->update(['login_fail' => $loginFail]);
                return $this->apiResponse->UnAuthorization(Lang::get('message.auth.invalid_login'));
            } else {
                // Send error notification email if failed exceed x times
                Mail::to($param['email'])->send(new InvalidLoginMail($user));
                return $this->apiResponse->UnAuthorization(Lang::get('message.auth.login_limit_exceeded'));
            }
        }
    }

    public function unlock($hashId)
    {
        // dd($hashId);
        $users = User::select('id')->get();
        foreach ($users as $user) {
            if (md5($user->id) == $hashId) {
                $userId = $user->id;
            }
        }
        if ($userId !== 0) {
            DB::table('users')->where('id', $userId)->update(['login_fail' => 0]);
            return redirect('http://localhost:5173/auth');
        }
    }

    public function testView()
    {
        return $this->apiResponse->success();
    }
}
