<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketRequest;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->supportTickets()->latest()->paginate(20);
    }

    public function store(SupportTicketRequest $request)
    {
        $ticket = $request->user()->supportTickets()->create($request->validated());

        return response()->json($ticket, 201);
    }
}
