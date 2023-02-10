<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Observer;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\Data\VerificationInterfaceFactory;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\IdenfyClientFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class VerifyCustomer implements ObserverInterface
{

    /** @var IdenfyClientFactory  */
    private IdenfyClientFactory $idenfyClientFactory;

    /** @var Json  */
    private Json $jsonSerializer;

    /** @var VerificationInterfaceFactory  */
    private VerificationInterfaceFactory $verificationFactory;

    /** @var VerificationRepositoryInterface  */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /**
     * @param IdenfyClientFactory $idenfyClientFactory
     * @param Json $jsonSerializer
     * @param VerificationInterfaceFactory $verificationFactory
     * @param VerificationRepositoryInterface $verificationRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        IdenfyClientFactory $idenfyClientFactory,
        Json $jsonSerializer,
        VerificationInterfaceFactory $verificationFactory,
        VerificationRepositoryInterface $verificationRepository,
        LoggerInterface $logger
    ) {
        $this->idenfyClientFactory = $idenfyClientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->verificationFactory = $verificationFactory;
        $this->verificationRepository = $verificationRepository;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        /** @var CartInterface $quote */
        $quote = $observer->getEvent()->getQuote();

        $customerId = (int) $order->getCustomerId();
        $clientId = $this->getClientId($order, $quote);

        if ($this->isCustomerVerified($customerId, $clientId)) {
            $this->logger->info(
                sprintf('Customer with id %s already verified. Skipping verification.', $customerId)
            );
            return;
        }

        try {
            $idenfyClient = $this->idenfyClientFactory->create();
        } catch (LocalizedException $e) {
            $this->logger->error(sprintf('Unable to verify customer: %s', $e->getMessage()));
            return;
        }

        $payload = [
            'clientId' => $clientId
        ];

        try {
            $response = $idenfyClient->post(
                'token',
                [
                    RequestOptions::JSON => $payload
                ]

            );
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('Unable to verify customer: %s', $e->getMessage()));
            return;
        }

        try {
            $response = $this->jsonSerializer->unserialize($response->getBody()->getContents());
        } catch (\InvalidArgumentException $e) {
            $this->logger->error(sprintf('Unable to process Idenfy API response: %s', $e->getMessage()));
            return;
        }

        $this->createVerificationRecord($response, $order, $clientId, $payload);
    }

    /**
     * @param OrderInterface $order
     * @param CartInterface $quote
     * @return string
     */
    private function getClientId(OrderInterface $order, CartInterface $quote): string
    {
        if (!$order->getCustomerIsGuest()) {
            return (string) $order->getCustomerId();
        }

        $customerIdentifier = 'guest-';
        $customerIdentifier .= strtolower($order->getCustomerFirstname());
        $customerIdentifier .= '-';
        $customerIdentifier .= strtolower($order->getCustomerLastname());

        return preg_replace('/\s+/', '-', $customerIdentifier);
    }

    private function createVerificationRecord(
        mixed $response,
        OrderInterface $order,
        string $clientId,
        array $payload
    ): void {
        $verification = $this->verificationFactory->create();

        $verification->setCustomerId($order->getCustomerId() ? (int) $order->getCustomerId() : null);
        $verification->setVerificationData($this->jsonSerializer->serialize($payload));
        $verification->setClientId($clientId);
        $verification->setResponse($response['message']);
        $verification->setAuthToken($response['authToken']);
        $verification->setScanReference($response['scanRef']);
        $verification->setDigitString($response['digitString']);
        $verification->setMessage($response['message']);

        try {
            $this->verificationRepository->save($verification);
        } catch (AlreadyExistsException $e) {
            $this->logger->error(sprintf('Unable to store verification record: %s', $e->getMessage()));
        }
    }

    /**
     * @param int $customerId
     * @param string $clientId
     * @return bool
     */
    private function isCustomerVerified(int $customerId, string $clientId): bool
    {
        try {
            if ($customerId) {
                $this->verificationRepository->getByCustomerId($customerId);
            } else {
                $this->verificationRepository->getByClientId($clientId);
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return true;
    }
}
