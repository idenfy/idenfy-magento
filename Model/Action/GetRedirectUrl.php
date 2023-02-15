<?php
declare(strict_types=1);

namespace Idenfy\customerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Action\GetClientId;
use Idenfy\CustomerVerification\Model\Action\GetAuthToken;
use Idenfy\CustomerVerification\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;

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

    /**
     * @param CheckoutSession $checkoutSession
     * @param GetClientId $getClientId
     * @param GetAuthToken $getAuthToken
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Config $config
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        GetClientId $getClientId,
        GetAuthToken $getAuthToken,
        VerificationRepositoryInterface $verificationRepository,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->getAuthToken = $getAuthToken;
        $this->verificationRepository = $verificationRepository;
        $this->config = $config;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function execute(): string
    {
        $quote = $this->checkoutSession->getQuote();

        $this->cleanExistingVerification($quote);

        $verification = $this->getAuthToken->execute($quote);

        if ($verification === null) {
            throw new LocalizedException(__('Unable to start verification. Please try again.'));
        }

        $baseUrl = $this->config->getApiBaseUrl();

        return $baseUrl . '/redirect?authToken=' . $verification->getAuthToken();

    }

    /**
     * @param CartInterface $quote
     * @return void
     */
    private function cleanExistingVerification(CartInterface $quote): void
    {
        $clientId = $this->getClientId->execute($quote);
        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return;
        }

        $this->verificationRepository->delete($verification);
    }
}
