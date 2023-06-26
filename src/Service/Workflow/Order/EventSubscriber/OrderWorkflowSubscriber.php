<?php
declare(strict_types=1);

namespace App\Service\Workflow\Order\EventSubscriber;

use App\Entity\WorkflowEntry;
use App\Service\Workflow\Order\Transition\CompleteOrder;
use App\Service\Workflow\Order\Transition\ConfirmOrder;
use App\Service\Workflow\Order\Transition\VerifyOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly VerifyOrder $verifyOrder,
        private readonly ConfirmOrder $confirmOrder,
        private readonly CompleteOrder $completeOrder,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.order_complete.transition.verify_order' => 'handleVerifyOrderTransition',
            'workflow.order_complete.transition.confirm_order' => 'handleConfirmOrderTransition',
            'workflow.order_complete.transition.complete_order' => 'handleCompleteOrderTransition',
        ];
    }

    public function handleVerifyOrderTransition(Event $event): void
    {
        /** @var WorkflowEntry $workflowEntry */
        $workflowEntry = $event->getSubject();
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->verifyOrder->handle($workflowEntry);
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->getConnection()->rollBack();

            throw $exception;
        }

    }

    public function handleConfirmOrderTransition(Event $event): void
    {
        /** @var WorkflowEntry $workflowEntry */
        $workflowEntry = $event->getSubject();
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->confirmOrder->handle($workflowEntry);
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->getConnection()->rollBack();

            throw $exception;
        }
    }

    public function handleCompleteOrderTransition(Event $event): void
    {
        /** @var WorkflowEntry $workflowEntry */
        $workflowEntry = $event->getSubject();
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->completeOrder->handle($workflowEntry);
            $this->entityManager->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->getConnection()->rollBack();

            throw $exception;
        }
    }
}
