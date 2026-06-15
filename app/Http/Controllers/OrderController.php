<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Medicine;

class OrderController extends Controller
{
public function createOrder(Request $request)
{
    $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'pharmacy_id' => 'required|exists:pharmacies,id',
        'items' => 'required|array|min:1',
        'items.*.medicine_id' => 'required|exists:medicines,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
    ]);
    DB::beginTransaction();

    try {
        $totalPrice = 0;

        foreach ($request->items as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        $order = Order::create([
            'supplier_id' => $request->supplier_id,
            'pharmacy_id' => $request->pharmacy_id,
            'date' => now()->toDateString(),
            'total_price' => $totalPrice,
            'status' => 'pending'
        ]);

        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'medicine_id' => $item['medicine_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }

        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الطلب بنجاح',
            'order_id' => $order->id
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'خطأ: ' . $e->getMessage()
        ], 500);
    }
}
    public function receiveOrder($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('items')->findOrFail($id);

            // تأكد ما يكون مستلم قبل
            if ($order->status === 'received') {
                return response()->json([
                    'message' => 'تم استلام الطلب مسبقاً'
                ], 400);
            }

            foreach ($order->items as $item) {
                $medicine = Medicine::find($item->medicine_id);
                $medicine->increment('quantity', $item->quantity);
            }

            $order->update([
                'status' => 'received'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم استلام الطلب وزيادة المخزون'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getOrders()
    {
        $orders = Order::with(['supplier', 'items.medicine'])
            ->latest()
            ->get();

        return response()->json($orders);
    }
  }
