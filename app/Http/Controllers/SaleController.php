<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function createSale(Request $request)
    {
        $request->validate([
            'pharmacy_id'         => 'required|exists:pharmacies,id',
            'pharmacist_id'       => 'nullable|exists:pharmacists,id',
            'employee_id'         => 'nullable|exists:employees,id',
            'customer_name'       => 'nullable|string',
            'payment_method'      => 'required|in:cash,card,insurance',
            'card_number'         => 'required_if:payment_method,card|digits:10',
            'items'               => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity'    => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $totalPrice = 0;

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

            if ($request->payment_method === 'insurance') {
                $totalPrice = $totalPrice * 0.80;
            }

            $sale = Sale::create([
                'pharmacy_id'    => $request->pharmacy_id,
                'pharmacist_id'  => $request->pharmacist_id,
                'employee_id'    => $request->employee_id,
                'customer_name'  => $request->customer_name,
                'payment_method' => $request->payment_method,
                'total_price'    => $totalPrice,
                'date'           => now()->toDateString(),
            ]);

            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'medicine_id' => $item['medicine_id'],
                    'quantity'    => $item['quantity'],
                    'price'       => $medicine->selling_price,
                ]);

                $medicine->decrement('quantity', $item['quantity']);
                $medicine->refresh();

                // ✅ FIX: نتحقق من نفاد المخزون أولاً قبل low_stock
                // لأنه إذا نفد ما بدنا إشعارين
                if ($medicine->quantity == 0) {
                    // ✅ NEW: إشعار out_of_stock (كان ناقص من createSale)
                    $alreadyNotified = \App\Models\Notification::where('pharmacy_id', $request->pharmacy_id)
                        ->where('type', 'out_of_stock')
                        ->where('message', 'LIKE', '%' . $medicine->name . '%')
                        ->exists();

                    if (!$alreadyNotified) {
                        Notification::create([
                            'pharmacy_id' => $request->pharmacy_id,
                            'title'       => 'نفاد المخزون ⚠️',
                            'message'     => 'دواء ' . $medicine->name . ' نفد من المخزون تماماً',
                            'type'        => 'out_of_stock',
                            'is_read'     => false,
                            'date'        => now(),
                        ]);
                    }
                } elseif ($medicine->quantity <= $medicine->reorder_level) {
                    // إشعار low_stock فقط إذا ما نفد (لتجنب إشعارين)
                    $alreadyNotified = Notification::where('pharmacy_id', $request->pharmacy_id)
                        ->where('type', 'low_stock')
                        ->where('message', 'LIKE', '%' . $medicine->name . '%')
                        ->exists();

                    if (!$alreadyNotified) {
                        Notification::create([
                            'pharmacy_id' => $request->pharmacy_id,
                            'title'       => 'تنبيه نقص مخزون',
                            'message'     => 'دواء ' . $medicine->name . ' كميته أصبحت ' . $medicine->quantity . ' فقط',
                            'type'        => 'low_stock',
                            'is_read'     => false,
                            'date'        => now(),
                        ]);
                    }
                }
            }

            Notification::create([
                'pharmacy_id' => $request->pharmacy_id,
                'title'       => 'عملية بيع جديدة',
                'message'     => 'تمت عملية بيع بقيمة ' . $totalPrice,
                'type'        => 'sale',
                'is_read'     => false,
                'date'        => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'        => 'تمت عملية البيع بنجاح',
                'sale_id'        => $sale->id,
                'total_price'    => $totalPrice,
                'items_count'    => count($request->items),
                'payment_method' => $request->payment_method,
                'date'           => $sale->date,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function getDailySales(Request $request)
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
        ]);

        $sales = Sale::where('pharmacy_id', $request->pharmacy_id)
            ->whereDate('date', now()->toDateString())
            ->with('items.medicine')
            ->get();

        return response()->json([
            'date'        => now()->toDateString(),
            'total_sales' => $sales->count(),
            'total_price' => $sales->sum('total_price'),
            'sales'       => $sales,
        ]);
    }

    public function getAllSales(Request $request)
    {
        $request->validate([
            'pharmacy_id' => 'required|exists:pharmacies,id',
            'filter'      => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $query = Sale::where('pharmacy_id', $request->pharmacy_id)
            ->with('items.medicine');

        if ($request->filter) {
            $query->whereBetween('created_at', match($request->filter) {
                'daily'   => [now()->startOfDay(),   now()->endOfDay()],
                'weekly'  => [now()->startOfWeek(),  now()->endOfWeek()],
                'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
                'yearly'  => [now()->startOfYear(),  now()->endOfYear()],
            });
        }

        $sales = $query->latest()->get();

        return response()->json([
            'total_sales' => $sales->count(),
            'total_price' => $sales->sum('total_price'),
            'sales'       => $sales,
        ]);
    }

    public function getEmployeeSales(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'filter'      => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        $query = Sale::where('employee_id', $request->employee_id)
            ->with('items.medicine');

        if ($request->filter) {
            $query->whereBetween('created_at', match($request->filter) {
                'daily'   => [now()->startOfDay(),   now()->endOfDay()],
                'weekly'  => [now()->startOfWeek(),  now()->endOfWeek()],
                'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
                'yearly'  => [now()->startOfYear(),  now()->endOfYear()],
            });
        }

        $sales = $query->latest()->get();

        return response()->json([
            'total_sales' => $sales->count(),
            'total_price' => $sales->sum('total_price'),
            'sales'       => $sales,
        ]);
    }
}
