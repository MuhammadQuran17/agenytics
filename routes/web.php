<?php

use App\Http\Controllers\AiChat\AiChatController;
use App\Http\Controllers\AiChat\Api\ChatStatusPollingController;
use App\Http\Controllers\AiChat\Api\SendMessageToAiController;
use App\Http\Controllers\ApiKey\ApiKeyController;
use App\Http\Controllers\Feedback\FeedbackCommentController;
use App\Http\Controllers\Feedback\FeedbackController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return view('terms-of-service');
})->name('terms-of-service');

Route::get('/documentation', function () {
    return view('documentation');
})->name('documentation');

// Feedback (FeatureRequest) Management
Route::prefix('feedback')->name('feedback.')->group(function () {
    Route::get('/', [FeedbackController::class, 'index'])->name('list');
    Route::get('/filter', [FeedbackController::class, 'filterData'])->name('filter');
    Route::get('/{feedback}', [FeedbackController::class, 'show'])->name('show');
    Route::get('/{feedback}/comments', [FeedbackCommentController::class, 'listOfComments'])->name('comments');
});

Route::middleware('auth')->group(function () {
    // Chat Management
    Route::prefix('chat')->name('chat.')->middleware('verified')->group(function () {
        Route::get('/create', [AiChatController::class, 'create'])->name('create');
        Route::post('/send', SendMessageToAiController::class)->name('send');
        Route::post('/status', ChatStatusPollingController::class)->name('status');
        Route::get('/{userChat?}', [AiChatController::class, 'index'])->name('index');
    });

    Route::redirect('dashboard', 'chat')->name('dashboard');

    // API Key Management
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::post('/check', [ApiKeyController::class, 'checkIfExists'])->name('checkIfExists');
        Route::post('/', [ApiKeyController::class, 'store'])->name('store');
        Route::delete('/', [ApiKeyController::class, 'clear'])->name('clear');
    });

    // Subscription Management
    Route::get('subscription-plans', [SubscriptionController::class, 'index'])->name('subscription-plans');
    Route::get('subscribe-or-purchase', [SubscriptionController::class, 'subscribeOrPurchase'])->name('subscribeOrPurchase');

    // Feedback (FeatureRequest) Management
    Route::prefix('feedback')->name('feedback.')->group(function () {
        Route::post('/', [FeedbackController::class, 'updateOrInsertNewFeedback'])->name('insert');
        Route::put('/{feedback}', [FeedbackController::class, 'updateOrInsertNewFeedback'])->name('update');
        Route::post('/{feedback}/vote', [FeedbackController::class, 'vote'])->name('vote');
        Route::post('/{feedback}/comment', [FeedbackCommentController::class, 'saveComment'])->name('comment.save');
        Route::post('/upload-image', [FeedbackController::class, 'uploadImage'])->name('upload-image');

        // Admin only routes
        Route::middleware('feedback.admin')->group(function () {
            Route::get('/{feedback}/edit', [FeedbackController::class, 'edit'])->name('edit');
            Route::patch('/{feedback}/edit', [FeedbackController::class, 'update'])->name('edit.update');
            Route::patch('/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('status.update');
            Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->name('destroy');
        });
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
