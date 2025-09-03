<?php

namespace App\Services\MicrosoftService;

use App\Models\Message;
use Exception;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\Messages\Item\Forward\ForwardPostRequestBody;
use Microsoft\Kiota\Abstractions\ApiException;

class ForwardEmailService
{
    public function __construct(
        private GraphServiceClientFactory $graphServiceClientFactory
    ) {}

    public function forwardEmail(Message $message, array $toRecipients): void
    {
        $graph = $this->graphServiceClientFactory->createGraphServiceClient();

        $recipientCollection = [];

        foreach ($toRecipients as $email) {
            $emailAddress = new EmailAddress;
            $emailAddress->setAddress($email);

            $recipient = new Recipient;
            $recipient->setEmailAddress($emailAddress);

            $recipientCollection[] = $recipient;
        }

        $body = new ForwardPostRequestBody;
        $body->setToRecipients($recipientCollection);

        try {
            $graph->me()
                ->messages()
                ->byMessageId($message->outlook_message_id)
                ->forward()
                ->post($body);
        } catch (ApiException $e) {
            /** @phpstan-ignore-next-line */
            throw new Exception('Something went wrong forwarding the email (API): '.var_export($e->getError(), true));
        } catch (Exception $e) {
            throw new Exception('Something went wrong forwarding the email: '.var_export($e->getMessage(), true));
        }
    }
}
