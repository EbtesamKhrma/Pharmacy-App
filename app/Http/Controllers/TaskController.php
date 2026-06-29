<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Employee;
use App\Models\Notification;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // ===== الصيدلاني: إضافة مهمة لموظف =====
    public function createTask(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
            'employee_id' => 'required|exists:employees,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // التحقق إن الموظف تابع لهاي الصيدلية
        $employee = Employee::where('id', $request->employee_id)
            ->where('pharmacy_id', $request->pharmacy_id)
            ->where('status', 'approved')
            ->first();

        if (!$employee) {
            return response()->json([
                'message' => 'الموظف غير موجود في هذه الصيدلية',
            ], 404);
        }

        $task = Task::create([
            'pharmacy_id' => $request->pharmacy_id,
            'employee_id' => $request->employee_id,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => 'pending',
        ]);

        // إشعار للصيدلية إن مهمة انضافت
        Notification::create([
            'pharmacy_id' => $request->pharmacy_id,
            'title'       => 'مهمة جديدة',
            'message'     => 'تم تعيين مهمة "' . $task->title . '" للموظف ' . $employee->name,
            'type'        => 'task',
            'is_read'     => false,
            'date'        => now(),
        ]);

        return response()->json([
            'message' => 'تم إضافة المهمة بنجاح',
            'task'    => $task,
        ], 201);
    }

    // ===== الصيدلاني: عرض كل مهام صيدلية =====
    public function getPharmacyTasks(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $tasks = Task::where('pharmacy_id', $request->pharmacy_id)
            ->with('employee:id,name,role')
            ->latest()
            ->get();

        return response()->json([
            'pending_count' => $tasks->where('status', 'pending')->count(),
            'done_count'    => $tasks->where('status', 'done')->count(),
            'tasks'         => $tasks,
        ]);
    }

    // ===== الصيدلاني: حذف مهمة =====
    public function deleteTask($id): \Illuminate\Http\JsonResponse
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json([
            'message' => 'تم حذف المهمة',
        ]);
    }

    // ===== الموظف: عرض مهامه =====
    public function getMyTasks(Request $request): \Illuminate\Http\JsonResponse
    {
        $employee = $request->user(); // من الـ token

        $tasks = Task::where('employee_id', $employee->id)
            ->latest()
            ->get();

        return response()->json([
            'pending_count' => $tasks->where('status', 'pending')->count(),
            'done_count'    => $tasks->where('status', 'done')->count(),
            'tasks'         => $tasks,
        ]);
    }

    // ===== الموظف: يعلم على المهمة "تم" =====
    public function markAsDone(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $employee = $request->user();

        $task = Task::where('id', $id)
            ->where('employee_id', $employee->id) // يتأكد إن المهمة إلو هو
            ->firstOrFail();

        if ($task->status === 'done') {
            return response()->json([
                'message' => 'المهمة منجزة مسبقاً',
            ], 400);
        }

        $task->update(['status' => 'done']);

        // إشعار للصيدلية إن الموظف أنجز المهمة
        Notification::create([
            'pharmacy_id' => $task->pharmacy_id,
            'title'       => 'مهمة منجزة ✅',
            'message'     => 'أنجز الموظف ' . $employee->name . ' المهمة: "' . $task->title . '"',
            'type'        => 'task',
            'is_read'     => false,
            'date'        => now(),
        ]);

        return response()->json([
            'message' => 'تم تعليم المهمة كمنجزة',
            'task'    => $task,
        ]);
    }
}
