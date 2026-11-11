<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class GetRedirectUrl
{

    /** @var CheckoutSession  */
    private CheckoutSession $checkoutSession;

    /** @var GetClientId  */
    private GetClientId $getClientId;

    /** @var GetAuthToken  */
    private GetAuthToken $getAuthToken;

    /** @var VerificationRepositoryInterface  */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var Config  */
    private Config $config;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param GetClientId $getClientId
     * @param GetAuthToken $getAuthToken
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        GetClientId $getClientId,
        GetAuthToken $getAuthToken,
        VerificationRepositoryInterface $verificationRepository,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->getAuthToken = $getAuthToken;
        $this->verificationRepository = $verificationRepository;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Start a new Idenfy verification for the current checkout session and return the URL to redirect to
     *
     * @return string
     * @throws LocalizedException
     */
    public function execute(): string
    {
        if (!$this->config->isEnabled()) {
            throw new LocalizedException(__('Customer verification is currently unavailable.'));
        }

        $quote = $this->checkoutSession->getQuote();
        $clientId = $this->getClientId->execute($quote);

        if ($clientId === null) {
            $this->logger->error('Unable to start verification: the checkout session has no saved quote.');
            throw new LocalizedException(__('Unable to start verification. Please try again.'));
        }

        $this->cleanExistingVerification($clientId);

        $verification = $this->getAuthToken->execute($quote);

        if ($verification === null) {
            throw new LocalizedException(__('Unable to start verification. Please try again.'));
        }

        return $this->config->getApiBaseUrl() . '/redirect?authToken=' . rawurlencode($verification->getAuthToken());
    }

    /**
     * Remove a previous, unfinished verification so a fresh auth token is generated for each attempt
     *
     * A successful verification is never removed.
     *
     * @param string $clientId
     * @return void
     * @throws LocalizedException
     */
    private function cleanExistingVerification(string $clientId): void
    {
        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return;
        }

        if ($verification->getIsVerified()) {
            throw new LocalizedException(__('You have already been verified.'));
        }

        $this->verificationRepository->delete($verification);
    }
}
