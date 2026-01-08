<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())->get();
        return view('dashboard.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $departments = TicketDepartment::all();
        return view('dashboard.tickets.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:ticket_departments,id',
            'subject' => 'required',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required',
        ]);
        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'department_id' => $data['department_id'],
            'subject' => $data['subject'],
            'priority' => $data['priority'],
            'status' => 'open',
        ]);
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $data['message'],
        ]);
        // Fire notification
        return redirect()->route('tickets.index')->with('success', 'Ticket created.');
    }

    public function show($id)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);
        return view('dashboard.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);
        $data = $request->validate(['message' => 'required']);
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $data['message'],
        ]);
        // Fire notification
        return back()->with('success', 'Reply sent.');
    }

    public function close($id)
    {
        $ticket = Ticket::where('user_id', Auth::id())->findOrFail($id);
        $ticket->status = 'closed';
        $ticket->save();
        // Fire notification
        return back()->with('success', 'Ticket closed.');
    }
}
