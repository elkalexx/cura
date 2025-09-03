<?php

namespace App\Services\MicrosoftService;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ContactService;
use App\Services\EnvelopeWriter;
use Exception;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Generated\Users\Item\MailFolders\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriterFactory;
use stdClass;

class SyncInboxEmailService
{
    public function __construct(
        private GraphServiceClientFactory $graphServiceClientFactory
    )
    {
    }

    public function syncInboxEmails(): void
    {
        $graph = $this->graphServiceClientFactory->createGraphServiceClient();

        $threeDaysAgoUtc = now()->utc()->subDays(3)->toIso8601String();
        $filter = "receivedDateTime ge $threeDaysAgoUtc";

        $query = MessagesRequestBuilderGetRequestConfiguration::createQueryParameters(
            expand: ['attachments'],
            filter: $filter,
            orderby: ['receivedDateTime desc'],
            top: 999
        );

        $config = new MessagesRequestBuilderGetRequestConfiguration(
            queryParameters: $query
        );

        try {
            $messages = $graph->me()
                ->mailFolders()
                ->byMailFolderId('inbox')
                ->messages()
                ->get($config)
                ->wait();
        } catch (ApiException $e) {
            /** @phpstan-ignore-next-line */
            Log::info('heey');
            throw new Exception('Something went wrong getting the inbox messages: ' . var_export($e->getError(), true));
        } catch (Exception $e) {
            Log::info('heey2');
            throw new Exception('Something went wrong getting the inbox messages' . var_export($e->getMessage(), true));
        }

        $writer = (new JsonSerializationWriterFactory)
            ->getSerializationWriter('application/json');
        $writer->writeCollectionOfObjectValues(null, $messages->getValue());

        $inboxMessages = json_decode($writer->getSerializedContent());

        dd($inboxMessages);

        if ($inboxMessages === null) {
            throw new \Exception('Something went wrong getting the inbox messages - inboxMessages is null');
        }

        foreach ($inboxMessages as $inboxMessage) {
            if (Message::where('outlook_message_id', $inboxMessage->id)->exists()) {
                continue;
            }

            $knowOutlookMessage = Message::where('outlook_conversation_id', $inboxMessage->conversationId)->first();

            if ($knowOutlookMessage) {
                $conversation = Conversation::where('id', $knowOutlookMessage->conversation_id)->first();
                $conversation->touch();
            } else {
                $conversation = $this->createConversation($inboxMessage);
            }

            $message = $this->createMessage($inboxMessage, $conversation);

            $from = ContactService::findOrCreate(
                $inboxMessage->from->emailAddress->address,
                $inboxMessage->from->emailAddress->name ?? null
            );

            $rawTo = $inboxMessage->toRecipients ?? [];
            $rawCc = $inboxMessage->ccRecipients ?? [];
            $rawBcc = $inboxMessage->bccRecipients ?? [];

            $to = array_map(
                fn($r) => ContactService::findOrCreate($r->emailAddress->address, $r->emailAddress->name ?? null),
                $rawTo
            );

            $cc = array_map(
                fn($r) => ContactService::findOrCreate($r->emailAddress->address, $r->emailAddress->name ?? null),
                $rawCc
            );

            $bcc = array_map(
                fn($r) => ContactService::findOrCreate($r->emailAddress->address, $r->emailAddress->name ?? null),
                $rawBcc
            );

            EnvelopeWriter::record($conversation, $message, $from, $to, $cc, $bcc);

        }
    }

    private function createConversation(stdClass $inboxMessage)
    {
        return Conversation::create([
            'subject' => $inboxMessage->subject,
        ]);
    }

    private function createMessage(stdClass $inboxMessage, Conversation $conversation)
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'body' => $inboxMessage->body->content,
            'direction' => 'inbound',
            'outlook_message_id' => $inboxMessage->id,
            'outlook_conversation_id' => $inboxMessage->conversationId,
            'attachments' => $inboxMessage->hasAttachments,
            'received_at' => $inboxMessage->crea
        ]);

        // attachments

        return $message;
    }
}
