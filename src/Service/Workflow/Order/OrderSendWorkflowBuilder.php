<?php
declare(strict_types=1);

namespace App\Service\Workflow\Order;

use App\Entity\Order;
use App\Entity\WorkflowEntry;
use App\Service\Workflow\Order\Stamp\OrderIdStamp;
use App\Service\Workflow\Envelope\WorkflowEnvelope;
use App\Service\Workflow\WorkflowType;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OrderSendWorkflowBuilder
{
    public function __construct(
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function create(
        Order $order,
        array $additionStamps = []
    ): WorkflowEntry {
        $envelope = new WorkflowEnvelope(
            array_merge([
                OrderIdStamp::createWithOrderId($order->getId()),
            ], $additionStamps
        ));

        /** @var array $stamps */
        $stamps = $this->normalizer->normalize($envelope, WorkflowEnvelope::class);

        return WorkflowEntry::create(
            WorkflowType::OrderSend,
            Transition::VerifyOrder->value,
            $stamps
        );
    }
}
