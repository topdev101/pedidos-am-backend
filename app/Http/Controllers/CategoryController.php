<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function search(Request $request)
    {
        $mod = new Category();
        if ($request->get('keyword') != '') {
            $keyword = $request->get('keyword');
            $mod = $mod->where('name', 'like', "%$keyword%");
        }
        $per_page = $request->get('per_page');
        if ($request->get('page')) {
            $data = $mod->paginate($per_page ? $per_page : 10);
            $company_id = $request->get('company_id');
            $data->each(function ($model) use ($company_id) {
                $mod_ordered = $model->ordered_items();
                $mod_received = $model->received_items();
                if ($company_id) {
                    $mod_ordered = $mod_ordered->whereHas('purchase_order', function ($query) use ($company_id) {
                        $query = $query->where('company_id', $company_id);
                    });
                    $mod_received = $mod_received->whereHas('purchase', function ($query) use ($company_id) {
                        $query = $query->where('company_id', $company_id);
                    });
                }
                $model->ordered_quantity = $mod_ordered->sum('quantity');
                $model->received_quantity = $mod_received->sum('quantity');
            });
        } else {
            $data = $mod->get();
        }

        return $this->sendResponse($data);
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);
        $model = Category::updateOrCreate(['id' => $request->get('id')], [
            'name' => $request->get('name'),
        ]);
        return $this->sendResponse($model);
    }

    public function getDetail($id)
    {
        return $this->sendResponse(Category::find($id));
    }

    public function delete($id)
    {
        Category::destroy($id);
        return $this->sendResponse();
    }
}
