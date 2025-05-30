<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('testview', [\App\Http\Controllers\APIController::class, 'testview']);

Route::post('/login', [\App\Http\Controllers\APIController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/list-user', [\App\Http\Controllers\APIController::class, 'listUser']);
    Route::post('/user-store', [\App\Http\Controllers\APIController::class, 'store']);
    Route::patch('/update-password', [\App\Http\Controllers\APIController::class, 'updatePassword']);
    Route::delete('/delete-user/{id}', [\App\Http\Controllers\APIController::class, 'deleteUser']);

    Route::get('/my-info', [\App\Http\Controllers\UserController::class, 'show']);
    Route::get('/list-suggest-friend', [\App\Http\Controllers\UserController::class, 'suggestFriend']);
    Route::get('/add-friend', [\App\Http\Controllers\UserController::class, 'addFriend']);
    Route::get('/list-friend-request', [\App\Http\Controllers\UserController::class, 'listFriendRequest']);
    Route::get('/accept', [\App\Http\Controllers\UserController::class, 'accept']);
    Route::get('/most-followed', [\App\Http\Controllers\UserController::class, 'mostFollowed']);
    Route::get('/search', [\App\Http\Controllers\UserController::class, 'search']);
    Route::get('/setDeviceToken', [\App\Http\Controllers\UserController::class, 'setDeviceToken']);
    Route::post('/create-post', [\App\Http\Controllers\PostController::class, 'store']);
    Route::get('/timeline', [\App\Http\Controllers\TimelineController::class, 'timeline']);
    Route::get('/add-favorite', [\App\Http\Controllers\TimelineController::class, 'addFavorite']);
    Route::get('/remove-favorite', [\App\Http\Controllers\TimelineController::class, 'removeFavorite']);

    Route::get('/like', [\App\Http\Controllers\TimelineController::class, 'like']);
    Route::get('/list-comment', [\App\Http\Controllers\TimelineController::class, 'listComment']);

    Route::post('/comment', [\App\Http\Controllers\TimelineController::class, 'postComment']);
});

Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
