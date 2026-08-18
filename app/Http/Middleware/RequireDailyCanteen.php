<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireDailyCanteen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs(
            'logout',
            'profile',
            'canteen.today',
            'canteen.store',
            'password.*'
        )) {
            return $next($request);
        }

        if (! $user->mustDeclareToday()) {
            return $next($request);
        }

        return redirect()
            ->route('canteen.today')
            ->with('error', 'Please declare what you ate from the canteen today before using the rest of the system.');
    }
}
