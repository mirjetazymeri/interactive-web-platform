<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContactFormRequest;
use App\Services\Mail\MailService;

class ContactController extends Controller
{
    public function send(ContactFormRequest $request, MailService $mail)
    {
        $data = $request->validated();

        $mail->forwardContactForm(
            senderName:  $data['name'],
            senderEmail: $data['email'],
            subject:     $data['subject'],
            message:     $data['message'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent. We will get back to you shortly.',
        ]);
    }
}
