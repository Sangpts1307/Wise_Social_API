<?php

namespace App\Helpers;

use App\Mail\InvalidLoginMail;
use App\Mail\RequestFriendEmail;
use Illuminate\Support\Facades\Mail;

/**
 * Helper method to send mail (003)
 * 
 * Send an email to user who receives a friend request
 * @return void
 */

class SendMail
{
  public function sendMail003($email, $user)
  {
    try {
      Mail::to($email)->send(
        new RequestFriendEmail($user)
      );
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
}
