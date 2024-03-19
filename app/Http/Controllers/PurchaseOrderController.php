<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Notification;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Traits\UploadAble;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    use UploadAble;

    public function search(Request $request) {
        $auth_user = Auth::user();

        $mod = new PurchaseOrder();

        $mod = $mod->with('supplier');

        if ($request->get('company_id') != ""){
            $company_id = $request->get('company_id');
            $mod = $mod->where('company_id', $company_id);
        }
        if ($request->get('reference_no') != ""){
            $reference_no = $request->get('reference_no');
            $mod = $mod->where('reference_no', 'LIKE', "%$reference_no%");
        }
        if ($request->get('supplier_id') != ""){
            $supplier_id = $request->get('supplier_id');
            $mod = $mod->where('supplier_id', $supplier_id);
        }
        if ($request->get('startDate') != '' && $request->get('endDate') != '') {
            if($request->get('startDate') == $request->get('endDate')) {
                $mod = $mod->whereDate('ordered_at', $request->get('startDate'));
            } else {
                $mod = $mod->whereBetween('ordered_at', [$request->get('startDate'), $request->get('endDate')]);
            }
        }
        if ($request->get('expiryStartDate') != '' && $request->get('expiryEndDate') != '') {
            if($request->get('expiryStartDate') == $request->get('expiryEndDate')) {
                $mod = $mod->whereDate('expiry_date', $request->get('expiryStartDate'));
            } else {
                $mod = $mod->whereBetween('expiry_date', [$request->get('expiryStartDate'), $request->get('expiryEndDate')]);
            }
        }
        if ($request->get('keyword') != ""){
            $keyword = $request->get('keyword');
            $mod = $mod->where(function($query) use($keyword){
                return $query->where('reference_no', 'LIKE', "%$keyword%")
                        ->orWhere('ordered_at', 'LIKE', "%$keyword%")
                        ->orWhere('total_amount', 'LIKE', "%$keyword%")
                        ->orWhereHas('company', function ($query) use ($keyword) {
                            return $query->where('name', 'like', "%$keyword%");
                        })
                        ->orWhereHas('supplier', function ($query) use ($keyword) {
                            return $query->where('company', 'like', "%$keyword%")
                                        ->orWhere('name', 'like', "%$keyword%");
                        });
            });
        }
        $sort_by_date = 'desc';
        if($request->get('sort_by_date') != ''){
            $sort_by_date = $request->get('sort_by_date');
        }

        $per_page = $request->get('per_page');
        $data = $mod->orderBy('ordered_at', $sort_by_date)->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function create(Request $request) {
        ini_set('max_execution_time', 0);
        $request->validate([
            'date'=>'required|string',
            'reference_no'=>'required|string',
            'supplier'=>'required',
            'discount' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_numeric($value) && !preg_match('/^\d+(\.\d+)?%$/', $value)) {
                        $fail(__('validation.discount_format', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);

        $items = json_decode($request->get('items'), true);
        if(PurchaseOrder::where('reference_no', $request->get('reference_no'))->where('supplier_id', $request->get('supplier'))->exists()){
            return $this->sendErrors(['reference_no' => [__('page.reference_no_taken')]], '', 422);
        }
        try {
            DB::beginTransaction();
            $model = new PurchaseOrder();
            $model->user_id = Auth::user()->id;
            $model->ordered_at = Carbon::parse($request->get('date'));
            $model->reference_no = $request->get('reference_no');
            $model->company_id = $request->get('company_id') ? $request->get('company_id') : Auth::user()->company_id;
            $model->supplier_id = $request->get('supplier');
            $model->discount_string = $request->get('discount');
            $model->note = $request->get('note');
            $model->total_amount = $request->get('total_amount');
            $model->save();

            $nameElements = [];
            if ($model->company) $nameElements[] = $model->company->name;
            if ($model->reference_no) $nameElements[] = $model->reference_no;
            if ($model->supplier->company) $nameElements[] = $model->supplier->company;
            $imageName = Str::slug(implode(' ', $nameElements), '_');
            if ($request->file("images")) {
                $this->uploadFiles($request->file('images'), $imageName, 'purchase_orders', $model);
            }
            foreach ($items as $key => $item) {
                $purchaseOrderItem = $model->items()->create([
                    'product' => $item['product'],
                    'cost' => $item['cost'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'],
                    'discount_string' => $item['discount_string'],
                    'category_id' => $item['category_id'],
                    'amount' => ($item['cost'] - $item['discount']) * $item['quantity'],
                ]);

                // Save Images
                if ($request->has("item_images_$key") && $request->file("item_images_$key")) {
                    $itemImageName = $imageName . '_' . Str::slug($item['product'], '_');
                    $this->uploadFiles($request->file("item_images_$key"), $itemImageName , 'purchase_orders_item', $purchaseOrderItem);
                }
            }
            $model->update(['discount' => $model->items()->sum('discount')]);
            // Assume that supplier discount is only percentage
            $supplierDiscount = $model->supplier->discount;
            if (strpos($supplierDiscount, '%') !== false) {
                $greaterDiscount = 0;
                foreach ($model->items as $item) {
                    $itemDiscount = $item->discount * $item->quantity;
                    $expectedDiscount = $item->amount * floatval($supplierDiscount) / 100;
                    if ($itemDiscount > $expectedDiscount) {
                        $greaterDiscount++;
                    }
                }
                if ($greaterDiscount > 0) {
                    $model->company->notifications()->create([
                        'reference_no' => $model->reference_no,
                        'supplier_id' => $model->supplier_id,
                        'message' => 'greater_discounted_order',
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
            return $this->sendErrors([$th->getMessage()], __('page.something_went_wrong'), 500);
        }
        return $this->sendResponse($model);
    }

    public function getDetail($id){
        $model = PurchaseOrder::find($id);
        return $this->sendResponse($model->load('items', 'supplier', 'user', 'images'));
    }

    public function update(Request $request){
        ini_set('max_execution_time', 0);
        $request->validate([
            'date'=>'required|string',
            'reference_no'=>'required|string',
            'supplier'=>'required',
            'discount' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!is_numeric($value) && !preg_match('/^\d+(\.\d+)?%$/', $value)) {
                        $fail(__('validation.discount_format', ['attribute' => $attribute]));
                    }
                },
            ],
        ]);
        $model = PurchaseOrder::find($request->get("id"));

        $model->ordered_at = Carbon::parse($request->get('ordered_at'))->format('Y-m-d H:i:s');
        $model->reference_no = $request->get('reference_no');
        $model->supplier_id = $request->get('supplier');
        $model->note = $request->get('note');
        $model->total_amount = $request->get('total_amount');
        $model->save();

        $nameElements = [];
        if ($model->company) $nameElements[] = $model->company->name;
        if ($model->reference_no) $nameElements[] = $model->reference_no;
        if ($model->supplier->company) $nameElements[] = $model->supplier->company;
        $imageName = Str::slug(implode(' ', $nameElements), '_');
        if ($request->file("images")) {
            $this->uploadFiles($request->file('images'), $imageName, 'purchase_orders', $model);
        }

        $items = json_decode($request->get('items'), true);
        foreach ($items as $key => $item) {
            if (isset($item['id'])) {
                $purchaseOrderItem = PurchaseOrderItem::find($item['id']);
            } else {
                $purchaseOrderItem = new PurchaseOrderItem();
            }
            $purchaseOrderItem->product = $item['product'];
            $purchaseOrderItem->cost = $item['cost'];
            $purchaseOrderItem->quantity = $item['quantity'];
            $purchaseOrderItem->discount = $item['discount'];
            $purchaseOrderItem->discount_string = $item['discount_string'];
            $purchaseOrderItem->category_id = $item['category_id'];
            $purchaseOrderItem->amount = ($item['cost'] - $item['discount']) * $item['quantity'];
            $purchaseOrderItem->purchase_order_id = $model->id;
            $purchaseOrderItem->save();

            // Save Images
            if ($request->has("item_images_$key") && $request->file("item_images_$key")) {
                $itemImageName = $imageName . '_' . Str::slug($item['product'], '_');
                $this->uploadFiles($request->file("item_images_$key"), $itemImageName , 'purchase_orders_item', $purchaseOrderItem);
            }
        }
        return $this->sendResponse($model);
    }

    public function delete($id) {
        if (Auth::user()->role === 'secretary') {
            return $this->sendErrors(null, __('page.not_allowed'), 403);
        }
        $model = PurchaseOrder::find($id);
        if ($model->purchases()->exists()) {
            return $this->sendErrors(null, __('page.purchase_order_cant_delete'), 400);
        }
        try {
            DB::beginTransaction();
            foreach ($model->images as $image) {
                if ($this->fileExists($image->path, $image->disk)) {
                    $this->deleteFile($image->path, $image->disk);
                }
                $image->delete();
            }
            foreach ($model->items as $item) {
                foreach ($item->images as $image) {
                    if ($this->fileExists($image->path, $image->disk)) {
                        $this->deleteFile($image->path, $image->disk);
                    }
                    $image->delete();
                }
                $item->delete();
            }
            $model->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return $this->sendResponse();
    }

    public function receive(Request $request){
        $request->validate([
            'store' => 'required',
            'reference_no' => 'required',
        ]);
        $purchase_order = PurchaseOrder::find($request->get('id'));
        if (Purchase::where('reference_no', $request->get('reference_no'))->where('supplier_id', $purchase_order->supplier_id)->exists()) {
            return $this->sendErrors(['reference_no' => [__('page.referece_no_taken')]], '', 422);
        }
        $purchase = new Purchase();
        $purchase->purchase_order_id = $purchase_order->id;
        $purchase->user_id = $purchase_order->user_id;
        $store_id = $request->get('store');
        if (!$store_id) {
            $store_id = $purchase_order->company->store_id;
        }
        $purchase->store_id = $store_id ? $store_id : null;
        $purchase->company_id = $purchase_order->company_id;
        $purchase->supplier_id = $purchase_order->supplier_id;
        $purchase->reference_no = $request->get('reference_no');
        $purchase->note = $request->get('note');
        $purchase->shipping_carrier = $request->get('shipping_carrier');
        $purchase->purchased_at = $purchase_order->ordered_at;
        $purchase->total_amount = $request->get('total_amount');
        $purchase->note = $purchase_order->note;
        $purchase->save();

        $nameElements = [];
        if ($purchase->company) $nameElements[] = $purchase->company->name;
        if ($purchase->reference_no) $nameElements[] = $purchase->reference_no;
        if ($purchase->supplier->name) $nameElements[] = $purchase->supplier->name;
        $imageName = Str::slug(implode(' ', $nameElements), '_');
        if ($request->file("images")) {
            $this->uploadFiles($request->file('images'), $imageName, 'purchases', $purchase);
        }

        $items = json_decode($request->get('items'), true);
        foreach($items as $item){
            $purchase_order_item = PurchaseOrderItem::find($item['id']);
            $purchaseItem = $purchase->items()->create([
                'product' => $item['product'],
                'cost' => $item['cost'] - $item['discount'],
                'quantity' => $item['receive'],
                'amount' => ($item['cost'] - $item['discount']) * $item['receive'],
                'category_id' => $purchase_order_item->category_id,
                'purchase_order_item_id' => $item['id'],
                'purchase_id' => $purchase->id,
            ]);
            foreach ($purchase_order_item->images as $image) {
                $purchaseItem->images()->create([
                    'mime' => $image->mime,
                    'disk' => $image->disk,
                    'path' => $image->path,
                ]);
            }
        }

        return $this->sendResponse($purchase);
    }

    public function searchReceivedOrders(Request $request){
        $mod = new Purchase();
        $mod = $mod->whereNotNull('purchase_order_id')->with('supplier');

        if ($request->get('purchase_order_id') != ""){
            $mod = $mod->where('purchase_order_id', $request->get('purchase_order_id'));
        }

        if ($request->get('company_id') != ""){
            $mod = $mod->where('company_id', $request->get('company_id'));
        }

        if ($request->get('reference_no') != ""){
            $reference_no = $request->get('reference_no');
            $mod = $mod->where('reference_no', 'LIKE', "%$reference_no%");
        }

        if ($request->get('supplier_id') != ""){
            $supplier_id = $request->get('supplier_id');
            $mod = $mod->where('supplier_id', $supplier_id);
        }

        if ($request->get('startDate') != '' && $request->get('endDate') != '') {
            if ($request->get('startDate') == $request->get('endDate')) {
                $mod = $mod->whereDate('purchased_at', $request->get('startDate'));
            } else {
                $mod = $mod->whereBetween('purchased_at', [$request->get('startDate'), $request->get('endDate')]);
            }
        }

        if ($request->get('keyword') != ""){
            $keyword = $request->get('keyword');
            $mod = $mod->where(function($query) use($keyword){
                return $query->where('reference_no', 'like', "%$keyword%")
                        ->orWhereHas('supplier', function ($query) use ($keyword) {
                            return $query->where('name', 'like', "%$keyword%")
                                        ->orWhere('brand', 'like', "%$keyword%");
                        })
                        ->orWhereHas('company', function ($query) use ($keyword) {
                            return $query->where('name', 'like', "%$keyword%");
                        })
                        ->orWhereHas('store', function ($query) use ($keyword) {
                            return $query->where('name', 'like', "%$keyword%");
                        })
                        ->orWhere('purchased_at', 'like', "%$keyword%")
                        ->orWhere('total_amount', 'like', "%$keyword%");
            });
        }
        $sort_by_date = $request->sort_by_date ?? 'desc';
        $per_page = $request->get('per_page');
        $data = $mod->orderBy('purchased_at', $sort_by_date)->paginate($per_page);
        return $this->sendResponse($data);
    }

    public function getNextReferenceNo() {
        $lastModel = PurchaseOrder::orderBy('id', 'desc')->first();
        if ($lastModel) {
            $lastReferenceNo = $lastModel->reference_no;
            $lastReferenceNo = explode("-", $lastReferenceNo);
            $lastReferenceNo = isset($lastReferenceNo[1]) ? $lastReferenceNo[1] : 1;
            $lastReferenceNo = str_pad($lastReferenceNo + 1, 6, "0", STR_PAD_LEFT);
            $referenceNo = "PEDIDO-". $lastReferenceNo;
        } else {
            $referenceNo = "PEDIDO-000001";
        }
        return $this->sendResponse($referenceNo);
    }
}
