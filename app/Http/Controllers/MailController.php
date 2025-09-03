<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\MicrosoftService\ForwardEmailService;
use App\Services\MicrosoftService\ReplyEmailService;
use App\Services\MicrosoftService\SyncInboxEmailService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MailController extends Controller
{
    public function index()
    {
        $messages = Message::with(['conversation', 'senderParticipant.contact'])->where('direction', 'inbound')->get();

        return Inertia::render('mails/Index', [
            'messages' => $messages,
        ]);
    }

    public function show(Message $message)
    {
        $message->load(['conversation', 'senderParticipant.contact']);

        return Inertia::render('mails/Show', [
            'message' => $message,
        ]);
    }

    public function sync(SyncInboxEmailService $syncInboxEmailService): void
    {

        try {
            $syncInboxEmailService->syncInboxEmails();
        } catch (Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'service' => 'Something went wrong syncing the emails',
            ]);
        }
    }

    public function reply(Request $request, Message $message, ReplyEmailService $replyEmailService)
    {
        $validated = $request->validate([
            'body' => 'required|string'
        ]);

        try {
            $replyEmailService->replyToAllEmail($validated, $message);
        } catch (Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'service' => 'Something went wrong replying to this email'
            ]);
        }
    }

    public function forward(Request $request, Message $message, ForwardEmailService $emailService)
    {

        $validated = $request->validate([
            'recipients'   => 'required_without:customEmail|array',
            'recipients.*' => 'email', // each item must be a valid email
            'customEmail'  => 'required_without:recipients|nullable|email',
        ]);

        $emails = [];

        if (!empty($validated['recipients']) && is_array($validated['recipients'])) {
            $emails = array_merge($emails, $validated['recipients']);
        }

        if (!empty($validated['customEmail'])) {
            $emails[] = $validated['customEmail'];
        }

        $emails = array_values(array_filter(array_unique($emails)));

        try {
            $emailService->forwardEmail($message, $emails);
        } catch (Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'service' => 'Something went wrong forwarding this email'
            ]);
        }
    }
}
