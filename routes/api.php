<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicationsController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\UserController;
use GuzzleHttp\Middleware;
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
Route::middleware('auth:sanctum')->get('/getMessage/{id}', function (Request $request) {
    return $request->user();
});
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/sendPasswordResetLink', [PasswordResetRequestController::class, 'sendEmail']);

    Route::post('/reset-password', [PasswordResetController::class, 'reset']);

    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/userById/{userId}', [AuthController::class, 'userById']);
    Route::post('/ajouteTask', [CalendarController::class, 'store']);
    Route::put('/modifierTache/{id}', [CalendarController::class, 'updateTask']);
    Route::delete('/supprimerTask/{id}', [CalendarController::class, 'destroy']);

    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/secteurs', [AuthController::class, 'secteurs']);
    Route::put('/updateprofile', [AuthController::class, 'updateProfile']);
    // Route::get('/startup',[AuthController::class,'getStartupDetailsForUser']);
    Route::get('/startup/{id}', [AuthController::class, 'getStartupDetailsForUserById']);
    Route::get('/getTasks', [CalendarController::class, 'getTasks']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/getUserType', [AuthController::class, 'getUserType']);
    Route::get('/getMessage/{id}', [CalendarController::class, 'getMessage']);
    //Routes Publication

    Route::get('/publications', [PublicationsController::class, 'index']);
    Route::post('/publication', [PublicationsController::class, 'store']);
    Route::get('/publicationsUser', [PublicationsController::class, 'userProfilePublications']);
    Route::get('/userProfilePublicationsId/{id}', [PublicationsController::class, 'userProfilePublicationsId']);
    Route::get('/publications/{id}', [PublicationsController::class, 'show']);
    Route::delete('/publications/{id}/', [PublicationsController::class, 'destroy']);

    Route::get('/nbposts', [PublicationsController::class, 'nbPosts']);

    Route::post('/liked/{id}', [PublicationsController::class, 'like']);
    Route::put('/disliked/{id}', [PublicationsController::class, 'dislike']);

    Route::get('/search', [UserController::class, 'search']);
    Route::post('/uploadAvatar', [UserController::class, 'upload']);
    // Routes reclamations

    Route::post('/reclamation', [ReclamationController::class, 'store']);
    Route::get('/listReclamation', [ReclamationController::class, 'index']);
    Route::get('/detailReclamation/{id}', [ReclamationController::class, 'show']);

    // Routes notifications
    Route::get('/notifications', [NotificationController::class, 'LikedNotifications']);
    Route::get('/markAllRead', [NotificationController::class, 'markAsReadAll']);
    Route::get('/markAsRead/{id}', [NotificationController::class, 'markAsRead']);
    Route::get('/countNotifications', [NotificationController::class, 'countNotifications']);

    //Routes follower
    Route::post('/follow', [FollowController::class, 'follow']);
    Route::delete('/unfollow/{id}', [FollowController::class, 'unfollow']);
    Route::get('/checkFollow/{userId}', [FollowController::class, 'checkFollow']);
    Route::get('/followerPersonns', [FollowController::class, 'PersonnsFollow']);
    Route::get('/getUtilisateurs', [FollowController::class, 'getUtilisateurs']);

    //juste test de paiement
    Route::post('/generate-payment', [PaymentController::class, 'generatePayment']);
    Route::post('/createCompte', [PaymentController::class, 'store']);
    Route::get('/getCompteCorrdonnees', [PaymentController::class, 'getCompteFlouci']);

    Route::post('/verify/{id}', [PaymentController::class, 'verifyPayment']);
    Route::get('/getSecret/{id_startup}', [PaymentController::class, 'getSecret']);
    Route::get('/investmentHistory', [PaymentController::class, 'investmentHistory']);

    //chat
    Route::post('/message/{id}', [ChatController::class, 'store']);
    Route::get('/showConversation/{id}', [ChatController::class, 'showConversation']);
    Route::get('/showPersonsConversation', [ChatController::class, 'showPersonsConversation']);

    //payment
    Route::get('/getfloucistartup/{id}', [PaymentController::class, 'getStartupDetails']);
    Route::post('checkFlouciExistence', [PaymentController::class, 'checkFlouciExistence']);
    Route::get('/investorsTransactions', [PaymentController::class, 'investorsAndTransactionDatesOfUserStartup']);
    Route::get('/getTaskEnAttente', [CalendarController::class, 'getTaskEnAttente']);
    Route::put('/accept/{id}', [CalendarController::class, 'acceptTask']);
    Route::delete('/delete/{task}', [CalendarController::class, 'deleteTask']);

    //count
    Route::get('/count', [UserController::class, 'CountNumber']);

});
