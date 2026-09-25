<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.messages.index', [
            'messages' => ContactMessage::when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function show(ContactMessage $message): View
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.messages.show', compact('message'));
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate(['admin_reply' => ['required', 'string', 'max:5000']]);

        Mail::raw($data['admin_reply'], function (Message $mail) use ($message) {
            $mail->to($message->email, $message->name)->subject('Re: '.$message->subject);
        });

        $message->update(['admin_reply' => $data['admin_reply'], 'status' => 'replied', 'replied_at' => now()]);

        return back()->with('success', 'Reply sent to '.$message->email.'.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'Message deleted.');
    }
}
