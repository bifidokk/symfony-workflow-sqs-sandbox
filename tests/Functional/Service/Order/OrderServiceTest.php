<?php
declare(strict_types=1);

namespace App\Tests\Functional\Service\Order;

use App\Entity\WorkflowEntry;
use App\Repository\WorkflowEntryRepository;
use App\Service\Order\OrderService;
use App\Service\Workflow\Order\Stamp\OrderIdStamp;
use App\Service\Workflow\Order\State;
use App\Service\Workflow\WorkflowEnvelope;
use App\Service\Workflow\WorkflowType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OrderServiceTest extends WebTestCase
{
    private OrderService $orderService;
    private EntityManagerInterface $entityManager;
    private WorkflowEntryRepository $workflowEntryRepository;
    private DenormalizerInterface $denormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = self::getContainer()->get(OrderService::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->workflowEntryRepository = self::getContainer()->get(WorkflowEntryRepository::class);
        $this->denormalizer = self::getContainer()->get(DenormalizerInterface::class);
    }

    /**
     * @test
     */
    public function itCreatesOrder(): void
    {
        $order = $this->orderService->createOrder();
        $this->entityManager->refresh($order);

        $this->assertTrue($order->isCompleted());

        $workflowEntry = $this->workflowEntryRepository->findOneBy(
            [],
            ['createdAt' => 'desc'],
        );

        $this->assertInstanceOf(WorkflowEntry::class, $workflowEntry);
        $this->entityManager->refresh($workflowEntry);

        $this->assertEquals(WorkflowType::OrderComplete, $workflowEntry->getWorkflowType());
        $this->assertEquals(State::Completed->value, $workflowEntry->getCurrentState());

        /** @var WorkflowEnvelope $envelope */
        $envelope = $this->denormalizer->denormalize($workflowEntry->getStamps(), WorkflowEnvelope::class);
        $this->assertTrue($envelope->hasStampWithType(OrderIdStamp::class));

        $stamp = $envelope->getStamp(OrderIdStamp::class);
        $this->assertInstanceOf(OrderIdStamp::class, $stamp);

        $this->assertEquals($order->getId(), $stamp->getOrderId());
    }
}