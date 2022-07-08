<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvoiceMaster;
use App\Models\InvoiceDetail;
use Illuminate\Support\Facades\DB;
use Exception;
use Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function storeInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice' => 'required | array',
            'customer_name' => 'required',
            'invoice.*.product_id' => 'required',
            'invoice.*.rate' => 'required',
            'invoice.*.unit' => 'required',
            'invoice.*.qty' => 'required',
            'invoice.*.disc' => 'required',
            'invoice.*.net' => 'required',
            'invoice.*.total' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }
        DB::beginTransaction();
        try{
            if(count($request->invoice) > 0)
            {
                $latestRecord = InvoiceMaster::latest()->first();
                $invoiceMaster = new InvoiceMaster;
                $invoiceMaster->invoice_no = isset($latestRecord) ? $latestRecord->invoice_no + 1 : 1;
                $invoiceMaster->invoice_date = date("Y-m-d");
                $invoiceMaster->customer_name = $request->customer_name;
                $invoiceMaster->total_amount = 0;
                $invoiceMaster->save();
                $totalAmount = 0;
                foreach($request->invoice as $invoice)
                {
                    $invDetail = new InvoiceDetail;
                    $invDetail->invoice_id = $invoiceMaster->id;
                    $invDetail->product_id = $invoice['product_id'];
                    $invDetail->rate = $invoice['rate'];
                    $invDetail->unit = $invoice['unit'];
                    $invDetail->qty = $invoice['qty'];
                    $invDetail->disc_per = $invoice['disc'];
                    $invDetail->net_amount = $invoice['net'];
                    $invDetail->total_amount = $invoice['total'];
                    $invDetail->save();
                    $totalAmount += $invoice['total'];
                }
                $invoiceMaster->total_amount = $totalAmount;
                $invoiceMaster->save();
                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Submit successfully!']);
            }
            return response()->json(['status' => 'error', 'message' => 'Please add invoice!!']);
            
        }catch (Exception $e){
            DB::rollBack();
            Log::debug($e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'something went wrong']);
        }
    }
}
