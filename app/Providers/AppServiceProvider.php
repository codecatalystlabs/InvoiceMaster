<?php

namespace App\Providers;

use App\Models\EmailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            $unread = 0;
            $pendingMealReviews = 0;
            $pendingChangeRequests = 0;
            if (auth()->check()) {
                if (auth()->user()->canAccess('emails')) {
                    $unread = EmailMessage::query()
                        ->where('direction', 'incoming')
                        ->where('status', 'received')
                        ->count();
                }
                if (auth()->user()->canAccess('canteen.review')) {
                    $pendingMealReviews = \App\Models\CanteenMeal::query()->where('status', 'pending')->count();
                    $pendingChangeRequests = \App\Models\ChangeRequest::query()->where('status', 'pending')->count();
                }
            }
            $view->with([
                'unreadEmails' => $unread,
                'pendingMealReviews' => $pendingMealReviews,
                'pendingChangeRequests' => $pendingChangeRequests,
            ]);
        });
    }
}
