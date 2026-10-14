<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplySupportTicketRequest;
use App\Models\SupportTicket;

class SupportTicketController extends Controller
{
    public function index()
    {
        return SupportTicket::with('user:id,name,email')->latest()->paginate(20);
    }

    public function update(ReplySupportTicketRequest $request, SupportTicket $supportTicket)
    {
        $supportTicket->update([
            'admin_reply' => $request->admin_reply,
            'status' => $request->input('status', 'answered'),
        ]);

        return $supportTicket;
    }
}
