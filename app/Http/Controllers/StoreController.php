<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function search(Request $request)
    {
        $mod = new Store();
        $mod = $mod->with('company');
        if ($request->get('keyword') != '') {
            $keyword = $request->get('keyword');
            $mod = $mod->where('name', 'like', "%$keyword%");
        }
        $per_page = $request->get('per_page');
        if ($request->get('page')) {
            $data = $mod->paginate($per_page ? $per_page : 10);
        } else {
            if (auth()->user()->role !== 'admin') $mod = $mod->where('company_id', auth()->user()->company_id);
            $data = $mod->get();
        }
        return $this->sendResponse($data);
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'company' => 'required',
        ]);
        $model = Store::updateOrCreate(['id' => $request->get('id')], [
            'name' => $request->get('name'),
            'company_id' => $request->get('company'),
        ]);
        return $this->sendResponse($model);
    }

    public function getDetail($id)
    {
        return $this->sendResponse(Store::find($id)->load('company'));
    }

    public function delete($id)
    {
        if (Auth::user()->role === 'secretary') {
            return $this->sendErrors(null, __('page.not_allowed'), 403);
        }
        Store::destroy($id);
        return $this->sendResponse();
    }
}
