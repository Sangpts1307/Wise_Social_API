<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushToDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'PushToDevice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification to user\'s device';

    /**
     * Execute the console command to send push notifications to user devices.
     */
    public function handle()
    {
        // Retrieve 100 pending notifications with user device tokens
        $notifications = Notification::select('notifications.id', 'notifications.user_id', 'notifications.actor_id', 'notifications.content', 'device_tokens.token')
            ->leftJoin(
                'device_tokens',
                'notifications.user_id',
                'device_tokens.user_id'
            )
            ->where('notifications.status', Notification::STATUS_WAIT)
            ->orderBy('notifications.id', 'ASC')
            ->take(100)
            ->get();
        // Stop if no notifications are found
        if (count($notifications) <= 0) {
            $this->info("No notification in queue.");
        }

        // Initialize Firebase service
        $firebaseService = new FirebaseService();

        // Process each notification
        foreach ($notifications as $notification) {
            // Handle missing device token
            if (is_null($notification->token)) {
                DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update([
                        'status' => Notification::STATUS_FAIL,
                        // 'error_message' => 'Device token is missing.'
                    ]);
                $this->error("Notification {$notification->id} failed: Device token is missing.");
            } else {
                // Send notification to user device via FCM
                $sendFCM = $firebaseService->sendFCM($notification->content, $notification->token);
                if ($sendFCM) {
                    // Update notification status to done if sent successfully
                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update([
                            'status' => Notification::STATUS_DONE
                        ]);
                    $this->info("Notification {$notification->id} sent successfully to device.");
                } else {
                    // Update notification status to fail if sending failed
                    DB::table('notifications')
                        ->where('id', $notification->id)
                        ->update([
                            'status' => Notification::STATUS_FAIL,
                            // 'error_message' => 'Failed to send FCM notification.'
                        ]);
                    $this->error("Notification {$notification->id} failed: Failed to send FCM notification. Error: " . $firebaseService->getLastError());
                }
            }
        }
    }
}