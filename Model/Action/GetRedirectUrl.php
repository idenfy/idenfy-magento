<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Config;
use Idenfy\CustomerVerification\Model\Verification;
use InvalidArgumentException;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Starts (or resumes) the Idenfy verification for the current checkout session
 */
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

    /** @var StoreManagerInterface  */
    private StoreManagerInterface $storeManager;

    /** @var Json  */
    private Json $jsonSerializer;

    /** @var LoggerInterface  */
    private LoggerInterface $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param GetClientId $getClientId
     * @param GetAuthToken $getAuthToken
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        GetClientId $getClientId,
        GetAuthToken $getAuthToken,
        VerificationRepositoryInterface $verificationRepository,
        Config $config,
        StoreManagerInterface $storeManager,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->getAuthToken = $getAuthToken;
        $this->verificationRepository = $verificationRepository;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Return the URL of the verification for the current checkout session: the URL to embed in the iFrame, or the
     * URL to redirect to, depending on the integration mode
     *
     * An unfinished session is resumed rather than replaced, as every new session uses a verification credit.
     *
     * @param bool $restart Start a new session even if an unfinished one exists, e.g. after a failed attempt
     * @param string|null $returnUrl Page to return to after a redirect verification (same store only)
     * @return string
     * @throws LocalizedException
     */
    public function execute(bool $restart = false, ?string $returnUrl = null): string
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

        $existingVerification = $this->getExistingVerification($clientId);

        if ($existingVerification !== null) {
            if ($existingVerification->getIsVerified()) {
                throw new LocalizedException(__('You have already been verified.'));
            }

            if ($existingVerification->getStatus() === VerificationInterface::STATUS_REVIEWING) {
                throw new LocalizedException(
                    __('Your verification is being reviewed. You can complete your purchase as soon as it is approved.')
                );
            }

            if (!$restart && $this->isReusable($existingVerification)) {
                $this->logger->info(sprintf('Resuming the unfinished verification of client ID %s', $clientId));
                return $this->getVerificationUrl($existingVerification->getAuthToken());
            }

            // Start over: a webhook for the previous session no longer matches the stored scan reference
            $this->verificationRepository->delete($existingVerification);
        }

        $verification = $this->getAuthToken->execute($quote, $this->getValidatedReturnUrl($returnUrl));

        if ($verification === null) {
            throw new LocalizedException(__('Unable to start verification. Please try again.'));
        }

        return $this->getVerificationUrl($verification->getAuthToken());
    }

    /**
     * @param string $clientId
     * @return Verification|null
     */
    private function getExistingVerification(string $clientId): ?Verification
    {
        try {
            return $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Whether an unfinished session can still be completed by the customer
     *
     * @param Verification $verification
     * @return bool
     */
    private function isReusable(Verification $verification): bool
    {
        $lifetime = $this->config->getSessionReuseLifetime();
        $createdAt = $verification->getCreatedAt();

        if ($lifetime === 0
            || $verification->getStatus() !== VerificationInterface::STATUS_PENDING
            || $verification->getAuthToken() === ''
            || $createdAt === null
        ) {
            return false;
        }

        // Timestamps are stored in UTC
        $createdTimestamp = strtotime($createdAt . ' UTC');
        if ($createdTimestamp === false || time() - $createdTimestamp >= $lifetime) {
            return false;
        }

        // A session created for the other integration mode would navigate the wrong way when it finishes
        return $this->hasReturnUrls($verification) === !$this->config->isIframeMode();
    }

    /**
     * @param Verification $verification
     * @return bool
     */
    private function hasReturnUrls(Verification $verification): bool
    {
        try {
            $payload = $this->jsonSerializer->unserialize($verification->getVerificationData());
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return is_array($payload) && !empty($payload['successUrl']);
    }

    /**
     * Only allow returning to a page of the current store, so the endpoint cannot be used as an open redirect
     *
     * @param string|null $returnUrl
     * @return string|null
     */
    private function getValidatedReturnUrl(?string $returnUrl): ?string
    {
        if ($returnUrl === null || $returnUrl === '' || $this->config->isIframeMode()) {
            return null;
        }

        try {
            $store = $this->storeManager->getStore();
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $storeUrl = parse_url((string) $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true));
        $requestedUrl = parse_url($returnUrl);

        if (!is_array($storeUrl) || !is_array($requestedUrl)
            || ($requestedUrl['scheme'] ?? null) !== ($storeUrl['scheme'] ?? null)
            || strtolower($requestedUrl['host'] ?? '') !== strtolower($storeUrl['host'] ?? '')
            || ($requestedUrl['port'] ?? null) !== ($storeUrl['port'] ?? null)
            || isset($requestedUrl['user'])
        ) {
            $this->logger->warning(sprintf('Ignoring return URL "%s" outside of the store.', $returnUrl));
            return null;
        }

        return $returnUrl;
    }

    /**
     * @param string $authToken
     * @return string
     */
    private function getVerificationUrl(string $authToken): string
    {
        if ($this->config->isIframeMode()) {
            return $this->config->getUiBaseUrl() . '/?authToken=' . rawurlencode($authToken);
        }

        return $this->config->getApiBaseUrl() . '/redirect?authToken=' . rawurlencode($authToken);
    }
}
