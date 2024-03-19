<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function search(Request $request) {
        $mod = new Notification();
        $mod = $mod->with('supplier', 'notifiable');
        if ($request->get('page') != '') {
            $data = $mod->orderBy('created_at', 'desc')->paginate(20);
        } else {
            $data = $mod->orderBy('created_at', 'desc')->limit(3)->get();
        }
        return $this->sendResponse($data);
    }

    public function markAsRead(Request $request) {
        Notification::where('company_id', $request->get('company_id'))->update(['read' => 1]);
        return $this->sendResponse('Success');
    }

    public function getUnreadNotificationCount() {
        $mod = new Notification();
        $data = $mod->where('read', 0)->count();
        return $this->sendResponse($data);
    }

    public function delete($id) {
        Notification::destroy($id);
        return $this->sendResponse('Success');
    }

    public function deleteAll() {
        Notification::whereNotNull('id')->delete();
        return $this->sendResponse('Success');
    }
}
