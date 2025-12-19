<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the user is an admin user
 */
class FeedbackAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) return abort(401, 'Unauthorized');

        if (! $this->isAdminUser()) return abort(403, 'Unauthorized to perform this action on feedback');

        return $next($request);
    }

    public function isAdminUser(): bool
    {
        if (! Auth::check()) return false;

        $adminEmails = explode(',', config('feedback.admin_emails'));
        return in_array(Auth::user()->email, array_map('trim', $adminEmails));
    }
}
