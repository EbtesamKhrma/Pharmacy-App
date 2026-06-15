<?php

namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SaleController extends Controller
{
    public function createSale(Request $request)
    {
        $request->validate([
            'pharmacist_id'     => 'required|exists:pharmacists,id',
            'customer_name'     => 'nullable|string',
            'payment_method'    => 'required|in:cache,card,insurance',
            'items'             => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            $customer = Customer::create([
                'name' => $request->customer_name ?? null,
            ]);

            $totalPrice = 0;

            foreach ($request->items as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);

                if ($medicine->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'الكمية غير  متوفرة: ' . $medicine->name,
                    ], 400);
                }

                $totalPrice += $medicine->selling_price * $item['quantity'];
            }

            $sale = Sale::create([
                'pharmacist_id'  => $request->pharmacist_id,
                'customer_id'    => $customer->id,
                'date'           => now()->toDateString(),
                'total_price'    => $totalPrice,
                'payment_method' => $request->payment_method,
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
