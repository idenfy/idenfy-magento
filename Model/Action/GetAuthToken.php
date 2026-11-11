<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\Data\VerificationInterfaceFactory;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\IdenfyClientFactory;
use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\Argument\Interpreter\ServiceUrl;
use Psr\Log\LoggerInterface;

class GetAuthToken
{
    /**
     * Query parameter appended to the return URL so the checkout knows the customer comes back from Idenfy
     */
    public const RETURN_QUERY_PARAM = 'idenfy_return';

    /** @var IdenfyClientFactory  */
    private IdenfyClientFactory $idenfyClientFactory;

    /** @var Json  */
    private Json $jsonSerializer;

    /** @var VerificationInterfaceFactory  */
    private VerificationInterfaceFactory $verificationFactory;

    /** @var VerificationRepositoryInterface  */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var CheckCustomerVerification  */
    private CheckCustomerVerification $checkCustomerVerification;

    /** @var GetClientId  */
    private GetClientId $getClientId;

    /** @var UrlInterface  */
    private UrlInterface $urlBuilder;

    /** @var ServiceUrl  */
    private ServiceUrl $serviceUrlBuilder;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /**
     * @param IdenfyClientFactory $idenfyClientFactory
     * @param Json $jsonSerializer
     * @param VerificationInterfaceFactory $verificationFactory
     * @param VerificationRepositoryInterface $verificationRepository
     * @param CheckCustomerVerification $checkCustomerVerification
     * @param GetClientId $getClientId
     * @param UrlInterface $urlBuilder
     * @param ServiceUrl $serviceUrlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        IdenfyClientFactory $idenfyClientFactory,
        Json $jsonSerializer,
        VerificationInterfaceFactory $verificationFactory,
        VerificationRepositoryInterface $verificationRepository,
        CheckCustomerVerification $checkCustomerVerification,
        GetClientId $getClientId,
        UrlInterface $urlBuilder,
        ServiceUrl $serviceUrlBuilder,
        LoggerInterface $logger
    ) {
        $this->idenfyClientFactory = $idenfyClientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->verificationFactory = $verificationFactory;
        $this->verificationRepository = $verificationRepository;
        $this->checkCustomerVerification = $checkCustomerVerification;
        $this->getClientId = $getClientId;
        $this->urlBuilder = $urlBuilder;
        $this->serviceUrlBuilder = $serviceUrlBuilder;
        $this->logger = $logger;
    }

    /**
     * @param CartInterface $quote
     * @return VerificationInterface|null
     */
    public function execute(CartInterface $quote): ?VerificationInterface
    {
        $clientId = $this->getClientId->execute($quote);

        if ($clientId === null) {
            $this->logger->error('Unable to verify customer: the quote has no ID yet.');
            return null;
        }

        if ($this->checkCustomerVerification->execute($clientId)) {
            $this->logger->info(
                sprintf('Verification for client ID %s already successful. Skipping verification.', $clientId)
            );
            return null;
        }

        try {
            $idenfyClient = $this->idenfyClientFactory->create();
        } catch (LocalizedException $e) {
            $this->logger->error(sprintf('Unable to verify customer: %s', $e->getMessage()));
            return null;
        }

        $payload = $this->getPayload($clientId);

        try {
            $response = $idenfyClient->post(
                'token',
                [
                    RequestOptions::JSON => $payload
                ]
            );
        } catch (GuzzleException $e) {
            $this->logger->error(sprintf('Unable to verify customer: %s', $e->getMessage()));
            return null;
        }

        try {
            $responseData = $this->jsonSerializer->unserialize((string) $response->getBody());
        } catch (InvalidArgumentException $e) {
            $this->logger->error(sprintf('Unable to process Idenfy API response: %s', $e->getMessage()));
            return null;
        }

        if (!is_array($responseData)
            || empty($responseData['authToken'])
            || empty($responseData['scanRef'])
            || !is_string($responseData['authToken'])
            || !is_string($responseData['scanRef'])
        ) {
            $this->logger->error('Unable to process Idenfy API response: authToken or scanRef is missing.');
            return null;
        }

        return $this->createVerificationRecord($responseData, $quote, $clientId, $payload);
    }

    /**
     * @param array $response
     * @param CartInterface $quote
     * @param string $clientId
     * @param array $payload
     * @return VerificationInterface|null
     */
    private function createVerificationRecord(
        array $response,
        CartInterface $quote,
        string $clientId,
        array $payload
    ): ?VerificationInterface {

        $verification = $this->verificationFactory->create();

        $verification->setCustomerId($quote->getCustomerId() ? (int) $quote->getCustomerId() : null);
        $verification->setVerificationData($this->jsonSerializer->serialize($payload));
        $verification->setClientId($clientId);
        $verification->setAuthToken($response['authToken']);
        $verification->setScanReference($response['scanRef']);
        $verification->setDigitString(isset($response['digitString']) ? (string) $response['digitString'] : null);
        $verification->setMessage(isset($response['message']) ? (string) $response['message'] : '');

        try {
            return $this->verificationRepository->save($verification);
        } catch (Exception $e) {
            $this->logger->error(sprintf('Unable to store verification record: %s', $e->getMessage()));
            return null;
        }
    }

    /**
     * @param string $clientId
     * @return array
     */
    private function getPayload(string $clientId): array
    {
        $redirectUrl = $this->urlBuilder->getUrl(
            'checkout',
            [
                '_secure' => true,
                '_query' => [self::RETURN_QUERY_PARAM => 1],
                '_fragment' => 'payment'
            ]
        );
        $webhookUrl = $this->serviceUrlBuilder->evaluate(['path' => '/idenfy/process-verification']);

        return [
            'clientId' => $clientId,
            'successUrl' => $redirectUrl,
            'errorUrl' => $redirectUrl,
            'unverifiedUrl' => $redirectUrl,
            'callbackUrl' => $webhookUrl
        ];
    }
}
