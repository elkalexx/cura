<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageParticipant;
use Illuminate\Support\Facades\DB;

class EnvelopeWriter
{
    /**
     * @param  array<Contact>  $to
     * @param  array<Contact>  $cc
     * @param  array<Contact>  $bcc
     */
    public static function record(
        Conversation $conversation,
        Message $message,
        Contact $from,
        array $to,
        array $cc = [],
        array $bcc = []
    ): void {
        DB::transaction(function () use ($conversation, $message, $from, $to, $cc, $bcc) {

            $all = collect([$from])->merge($to)->merge($cc)->merge($bcc);
            $all->each(function ($contact) use ($conversation, $from) {
                ConversationParticipant::firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                ], [
                    'role' => $contact->is($from) ? 'originator' : 'participant',
                ]);
            });

            $rows = [['from', $from, 0]];
            $push = function (string $kind, array $list) use (&$rows) {
                foreach ($list as $index => $contact) {
                    $rows[] = [$kind, $contact, $index];
                }
            };

            $push('to', $to);
            $push('cc', $cc);
            $push('bcc', $bcc);

            MessageParticipant::insert(
                collect($rows)->map(fn ($row) => [
                    'message_id' => $message->id,
                    'contact_id' => $row[1]->id,
                    'kind' => $row[0],
                    'position' => $row[2],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
        });
    }
}
