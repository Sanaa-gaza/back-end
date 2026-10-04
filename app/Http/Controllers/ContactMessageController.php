<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    use ApiResponse;

    /**
     * Public endpoint. A logged-in user's email is taken from their account
     * (the approved design has no email field); a guest must supply one —
     * see ContactMessageRequest.
     */
    public function store(ContactMessageRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $contactMessage = ContactMessage::create([
            ...$request->validated(),
            'user_id' => $user?->id,
            'email' => $user?->email ?? $request->validated('email'),
        ]);

        Mail::to(config('contact.admin_email'))->send(new ContactMessageReceivedMail($contactMessage));

        return $this->success([], 'تم إرسال رسالتك بنجاح، سنتواصل معك قريباً.', 201);
    }
}
