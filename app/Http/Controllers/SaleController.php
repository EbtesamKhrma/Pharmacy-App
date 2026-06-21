<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Pharmacy;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function createSale(Request $request)
    {
        $request->validate([
            'pharmacist_id'        => 'required|exists:pharmacists,id',
            'payment_method'       => 'required|in:cash,card,insurance',
            'items'                => 'required|array|min:1',
            'items.*.medicine_id'  => 'required|exists:medicines,id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $totalPrice = 0;

            // حساب السعر والتأكد من الكمية
            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                if ($medicine->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'الكمية غير متوفرة: ' . $medicine->name,
                    ], 400);
                }

                $totalPrice += $medicine->selling_price * $item['quantity'];
            }

            // إنشاء عملية البيع
            $sale = Sale::create([
                'pharmacist_id'  => $request->pharmacist_id,
                'date'           => now()->toDateString(),
                'total_price'    => $totalPrice,
                'payment_method' => $request->payment_method,
            ]);

            // إضافة العناصر + تحديث الكمية + إشعار
            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'medicine_id' => $item['medicine_id'],
                    'quantity'    => $item['quantity'],
                    'price'       => $medicine->selling_price,
                ]);

                // إنقاص الكمية
                $medicine->decrement('quantity', $item['quantity']);

                // تحديث القيمة من الداتابيس
                $medicine->refresh();

                // 🔥 التحقق من المخزون
                if ($medicine->quantity <= $medicine->reorder_level) {

                    $pharmacy = Pharmacy::find($medicine->pharmacy_id);

                    Notification::create([
                        'pharamcist_id' => $pharmacy->pharamcist_id,
                        'title' => 'Low Stock',
                        'message' => $medicine->name . ' is running low',
                        'type' => 'warning',
                        'date' => now(),
                        'is_read' => false,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message'     => 'تمت عملية البيع بنجاح',
                'sale_id'     => $sale->id,
                'total_price' => $totalPrice,
                'date'        => $sale->date,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getDailySales(Request $request)
    {
        $request->validate([
            'pharmacist_id' => 'required|exists:pharmacists,id',
        ]);

        $todaySales = Sale::where('pharmacist_id', $request->pharmacist_id)
            ->whereDate('date', now()->toDateString())
            ->sum('total_price');

        return response()->json([
            'today_sales' => $todaySales,
            'date'        => now()->toDateString(),
        ]);
    }
}
