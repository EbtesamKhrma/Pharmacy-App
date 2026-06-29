<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح لك بالدخول',
            ], 401);
        }

        // نتحقق مين هو
        if ($role === 'pharmacist' && !($user instanceof \App\Models\Pharmacist)) {
            return response()->json([
                'message' => 'هذا الإجراء مخصص للصيدلاني فقط',
            ], 403);
        }

        if ($role === 'employee' && !($user instanceof \App\Models\Employee)) {
            return response()->json([
                'message' => 'هذا الإجراء مخصص للموظف فقط',
            ], 403);
        }

        return $next($request);
    }
}
