<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::all();
        return view('admin.tickets.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = Ticket::findOrFail($id);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $data = $request->validate(['message' => 'required']);
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $data['message'],
        ]);
        // Fire notification
        return back()->with('success', 'Reply sent.');
    }

    public function assign(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $data = $request->validate(['user_id' => 'required|exists:users,id']);
        // Assign logic here
        return back()->with('success', 'Ticket assigned.');
    }

    public function priority(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $data = $request->validate(['priority' => 'required|in:low,medium,high']);
        $ticket->priority = $data['priority'];
        $ticket->save();
        return back()->with('success', 'Priority updated.');
    }

    public function status(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $data = $request->validate(['status' => 'required|in:open,closed,pending,resolved']);
        $ticket->status = $data['status'];
        $ticket->save();
        return back()->with('success', 'Status updated.');
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = 'closed';
        $ticket->save();
        // Fire notification
        return back()->with('success', 'Ticket closed.');
    }
}
