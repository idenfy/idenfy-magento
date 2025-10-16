<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class NeedsVerification
{

    /** @var CheckoutSession  */
    private CheckoutSession $checkoutSession;

    /** @var GetClientId  */
    private GetClientId $getClientId;

    /** @var VerificationRepositoryInterface  */
    private VerificationRepositoryInterface $verificationRepository;

    /**
     * @param CheckoutSession $checkoutSession
     * @param GetClientId $getClientId
     * @param VerificationRepositoryInterface $verificationRepository
     */
    public function __construct(
        GetClientId $getClientId,
        VerificationRepositoryInterface $verificationRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->verificationRepository = $verificationRepository;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
            return false;
        }

        $clientId = $this->getClientId->execute($quote);

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return true;
        }

        $isVerified = $verification->getIsVerified();

        return !$isVerified;
    }
}
