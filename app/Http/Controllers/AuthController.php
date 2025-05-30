<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use App\Mail\RegisterMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    private $ApiResponse;

    public function __construct () {
        $this->ApiResponse = new ApiResponse();
    }
    /**
     * Controller for register function
     * 
     * @param \Illuminate\Http\Request  $request
     * @return string JSON
     */
    public function register(Request $request) {
        // Check if re_password match
        $param = $request->all();
        if ($param['password'] != $param['re_password']) {
            return $this->ApiResponse->BadRequest(Lang::get('message.auth.re_password_err')); //load from resources/lang/en/message.php
        }

        // Check if email is exists
        $checkEmail = User::where('email', $param['email'])->first();
        if ($checkEmail) {
            return $this->ApiResponse->BadRequest(Lang::get('message.auth.email_exists'));
        }

        // Create user
        $user = new User();
        $user->name = $param['name'];
        $user->email = $param['email'];
        $user->password = Hash::make($param['password']);
        $user->save();
        Mail::to($param['email'])->send(new RegisterMail($param));
        return $this->ApiResponse->success();
    }
}
