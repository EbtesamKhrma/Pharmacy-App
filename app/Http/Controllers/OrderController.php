<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // ===== إنشاء طلب =====
    public function createOrder(Request $request)
    {
        $request->validate([
            'supplier_id'          => 'required|exists:suppliers,id',
            'pharmacy_id'          => 'required|exists:pharmacies,id',
            'payment_method'       => 'required|in:cash,card',
            'items'                => 'required|array|min:1',
            'items.*.medicine_id'  => 'required|exists:medicines,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $totalPrice = 0;

            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                // التأكد إن الدواء تابع للمورد
                if ($medicine->supplier_id != $request->supplier_id) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'الدواء ' . $medicine->name . ' غير متوفر عند هذا المورد',
                    ], 400);
                }

                $totalPrice += $medicine->cost_price * $item['quantity'];
            }

            $order = Order::create([
                'supplier_id'    => $request->supplier_id,
                'pharmacy_id'    => $request->pharmacy_id,
                'date'           => now()->toDateString(),
                'total_price'    => $totalPrice,
                'payment_method' => $request->payment_method,
                'status'         => 'pending',
            ]);

            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                OrderItem::create([
                    'order_id'    => $order->id,
                    'medicine_id' => $item['medicine_id'],
                    'quantity'    => $item['quantity'],
                    'price'       => $medicine->cost_price,
                ]);
            }

            // إشعار الطلب
            Notification::create([
                'pharmacy_id' => $request->pharmacy_id,
                'title'       => 'طلب جديد',
                'message'     => 'تم إنشاء طلب جديد من ' . $order->supplier->name,
                'type'        => 'order',
                'is_read'     => false,
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'     => 'تم إنشاء الطلب بنجاح',
                'order_id'    => $order->id,
                'total_price' => $totalPrice,
                'status'      => 'pending',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== استلام الطلب =====
    public function receiveOrder($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('items')->findOrFail($id);

            if ($order->status === 'received') {
                return response()->json([
                    'message' => 'تم استلام الطلب مسبقاً',
                ], 400);
            }

            if ($order->status === 'cancelled') {
                return response()->json([
                    'message' => 'لا يمكن استلام طلب ملغي',
                ], 400);
            }

            foreach ($order->items as $item) {
                // نشوف إذا الدواء موجود بصيدلية الصيدلاني
                $existingMedicine = Medicine::where('pharmacy_id', $order->pharmacy_id)
                    ->where('name', $item->medicine->name)
                    ->first();

                if ($existingMedicine) {
                    // إضافة الكمية للدواء الموجود
                    $existingMedicine->increment('quantity', $item->quantity);
                } else {
                    // إنشاء دواء جديد بالصيدلية
                    Medicine::create([
                        'pharmacy_id'       => $order->pharmacy_id,
                        'supplier_id'       => $order->supplier_id,
                        'name'              => $item->medicine->name,
                        'category_medicine' => $item->medicine->category_medicine,
                        'cost_price'        => $item->medicine->cost_price,
                        'selling_price'     => $item->medicine->selling_price,
                        'manufacturer'      => $item->medicine->manufacturer,
                        'quantity'          => $item->quantity,
                        'reorder_level'     => $item->medicine->reorder_level,
                        'expire_date'       => $item->medicine->expire_date,
                        'qr_code'           => $item->medicine->qr_code,
                    ]);
                }
            }

            $order->update(['status' => 'received']);

            // إشعار الاستلام
            Notification::create([
                'pharmacy_id' => $order->pharmacy_id,
                'title'       => 'تم استلام الطلب',
                'message'     => 'تم استلام الطلب رقم ' . $order->id . ' وإضافته للمخزون',
                'type'        => 'order',
                'is_read'     => false,
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم استلام الطلب وتحديث المخزون بنجاح',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== إلغاء الطلب =====
    public function cancelOrder($id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'لا يمكن إلغاء الطلب، الحالة الحالية: ' . $order->status,
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        // إشعار الإلغاء
        Notification::create([
            'pharmacy_id' => $order->pharmacy_id,
            'title'       => 'تم إلغاء الطلب',
            'message'     => 'تم إلغاء الطلب رقم ' . $order->id,
            'type'        => 'order',
            'is_read'     => false,
            'date'        => now(),
        ]);

        return response()->json([
            'message' => 'تم إلغاء الطلب بنجاح',
        ]);
    }

    // ===== عرض كل الطلبات =====
    public function getOrders(Request $request)
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $orders = Order::with(['supplier', 'items.medicine'])
            ->where('pharmacy_id', $request->pharmacy_id)
            ->latest()
            ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    // ===== عرض طلب واحد =====
    public function getOrder($id)
    {
        $order = Order::with(['supplier', 'items.medicine'])->findOrFail($id);

        return response()->json([
            'order' => $order,
        ]);
    }
}
