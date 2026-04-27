<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'max:40'],
            'subject' => ['required', 'max:180'],
            'message' => ['required', 'max:3000'],
        ]);

        SupportMessage::create($data + ['user_id' => optional($request->user())->id]);

        return back()->with('status', 'Support message submitted.');
    }

    public function updateStatus(Request $request, SupportMessage $supportMessage)
    {
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,resolved']]);
        $supportMessage->update($data);

        return back()->with('status', 'Support message updated.');
    }
}
