<?php

namespace App\Services\MicrosoftService;

use App\Models\Message;
use App\Services\ContactService;
use App\Services\EnvelopeWriter;
use Exception;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message as OutlookMessage;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\ReplyAll\ReplyAllPostRequestBody;
use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Serialization\Json\JsonSerializationWriterFactory;

class ReplyEmailService
{
    public function __construct(
        private GraphServiceClientFactory $graphServiceClientFactory
    )
    {
    }

    public function replyToAllEmail(array $data, Message $message)
    {
        $graph = $this->graphServiceClientFactory->createGraphServiceClient();

        $newMessage = $this->createMessage($data, $message);

        $itemBody = new ItemBody;
        $itemBody->setContentType(new BodyType(BodyType::HTML));
        $itemBody->setContent($data['body']);

        $outlookMessage = new OutlookMessage;
        $outlookMessage->setBody($itemBody);

        // attachments

        $body = new ReplyAllPostRequestBody;
        $body->setMessage($outlookMessage);

        try {
            $graph->me()
                ->messages()
                ->byMessageId($message->outlook_message_id)
                ->replyAll()
                ->post($body);
        } catch (ApiException $e) {
            /** @phpstan-ignore-next-line */
            throw new Exception('Something went wrong replying to the email: ' . var_export($e->getError(), true));
        } catch (Exception $e) {
            throw new Exception('Something went wrong replying to the email: ' . var_export($e->getMessage(), true));
        }

        $this->updateMessageAndConversation($message, $graph, $message->conversation_id);

        //@todo fix this to the proper E-Mail
        $sender = ContactService::findOrCreate('');

        $participants = $message->envelope()->with('contact')->get();

        $to = [];
        $cc = [];
        $bcc = [];

        foreach ($participants as $part) {
            if ($part->contact_id === $sender->id) {
                continue;
            }

            switch ($part->kind) {
                case 'from':
                case 'to':
                    $to[$part->contact_id] = $part->contact;
                    break;
                case 'cc':
                    $cc[$part->contact_id] = $part->contact;
                    break;
                case 'bcc':
                    $bcc[$part->contact_id] = $part->contact;
                    break;
            }
        }

        EnvelopeWriter::record(
            $message->conversation,
            $newMessage,
            $sender,
            array_values($to),
            array_values($cc),
            array_values($bcc)
        );
    }

    private function createMessage(array $data, Message $message): Message
    {
        $message = Message::create([
            'conversation_id' => $message->conversation_id,
            'body' => $data['body'],
            'direction' => 'outbound',
            'attachments' => false,
        ]);

        // attachments

        return $message;
    }

    private function updateMessageAndConversation(Message $message, GraphServiceClient $graph): void
    {
        $outlookConversationId = $message->outlook_conversation_id;
        $query = MessagesRequestBuilderGetRequestConfiguration::createQueryParameters(
            filter: "conversationId eq '$outlookConversationId'",
            top: 1
        );

        $config = new MessagesRequestBuilderGetRequestConfiguration(queryParameters: $query);

        try {
            $messages = $graph->me()
                ->messages()
                ->get($config)
                ->wait();
        } catch (ApiException $e) {
            /** @phpstan-ignore-next-line */
            throw new Exception("Something went wrong getting the outlook message: " . var_export($e->getError()), true,);
        } catch (Exception $e) {
            throw new Exception('Something went wrong getting the outlook message: ' . var_export($e->getMessage(), true));
        }

        $writer = (new JsonSerializationWriterFactory)
            ->getSerializationWriter('application/json');
        $writer->writeCollectionOfObjectValues(null, $messages->getValue());

        $outlookMessage = json_decode($writer->getSerializedContent());

        $message->outlook_message_id = $outlookMessage[0]->id;
        $message->save();

        $message->conversation->touch();
    }
}
