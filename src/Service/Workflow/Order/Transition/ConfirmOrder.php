<?php
declare(strict_types=1);

namespace App\Service\Workflow\Order\Transition;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Workflow\Exception\WorkflowInternalErrorException;
use App\Service\Workflow\Order\Stamp\OrderIdStamp;
use App\Service\Workflow\Order\State;
use App\Service\Workflow\Order\Transition;
use App\Service\Workflow\Stamp\ThrowExceptionStamp;
use App\Service\Workflow\Stamp\WorkflowInternalErrorStamp;
use App\Service\Workflow\Envelope\WorkflowEnvelope;
use App\Service\Workflow\WorkflowTransitionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ConfirmOrder implements WorkflowTransitionInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly MailerInterface $mailer,
        private readonly string $orderConfirmationEmail,
    ) {
    }

    public function handle(WorkflowEnvelope $envelope): WorkflowEnvelope
    {
        /** @var OrderIdStamp $orderIdStamp */
        $orderIdStamp = $envelope->getStamp(OrderIdStamp::class);
        $orderId = $orderIdStamp->getOrderId();

        $order = $this->orderRepository->find($orderId);

        if (!$order instanceof Order) {
            throw new WorkflowInternalErrorException(sprintf('Order %s not found', $orderId));
        }

        $this->mailer->send(
            (new Email())
                ->from('admin@order.io')
                ->to($this->orderConfirmationEmail)
                ->subject('Order confirmed')
                ->text(sprintf('Order %s confirmed', $orderId))
        );

        /**
         * This block simulates one time error to allow retry the workflow later
         */
        if ($envelope->hasStampWithType(ThrowExceptionStamp::class)
            && !$envelope->hasStampWithType(WorkflowInternalErrorStamp::class)
        ) {
            throw new WorkflowInternalErrorException('An internal error occurred');
        }

        return $envelope;
    }

    public function getNextTransition(): ?string
    {
        return Transition::CompleteOrder->value;
    }

    public function getState(): ?string
    {
        return State::Confirmed->value;
    }
}
