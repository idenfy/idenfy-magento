<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\ViewModel;

use Idenfy\CustomerVerification\Model\Action\GetAuthToken;
use Idenfy\CustomerVerification\Model\ButtonStyle;
use Idenfy\CustomerVerification\Model\Config;
use Idenfy\CustomerVerification\Observer\PreventUnverifiedOrder;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\Argument\Interpreter\ServiceUrl;

/**
 * Data for the verification modal on the checkout
 *
 * Contains no customer specific data, so the output stays safe on pages that are cached. The verification status is
 * read from window.checkoutConfig or requested by the modal script.
 */
class VerificationModal implements ArgumentInterface
{
    /** @var Config */
    private Config $config;

    /** @var ButtonStyle */
    private ButtonStyle $buttonStyle;

    /** @var ServiceUrl */
    private ServiceUrl $serviceUrl;

    /** @var UrlInterface */
    private UrlInterface $urlBuilder;

    /** @var AssetRepository */
    private AssetRepository $assetRepository;

    /** @var JsonHexTag */
    private JsonHexTag $jsonSerializer;

    /**
     * @param Config $config
     * @param ButtonStyle $buttonStyle
     * @param ServiceUrl $serviceUrl
     * @param UrlInterface $urlBuilder
     * @param AssetRepository $assetRepository
     * @param JsonHexTag $jsonSerializer
     */
    public function __construct(
        Config $config,
        ButtonStyle $buttonStyle,
        ServiceUrl $serviceUrl,
        UrlInterface $urlBuilder,
        AssetRepository $assetRepository,
        JsonHexTag $jsonSerializer
    ) {
        $this->config = $config;
        $this->buttonStyle = $buttonStyle;
        $this->serviceUrl = $serviceUrl;
        $this->urlBuilder = $urlBuilder;
        $this->assetRepository = $assetRepository;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @return bool
     */
    public function isClosable(): bool
    {
        return $this->config->isModalClosable();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->config->getModalTitle();
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->config->getModalText();
    }

    /**
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->config->getButtonLabel();
    }

    /**
     * @return string
     */
    public function getButtonClass(): string
    {
        return $this->buttonStyle->getCssClass();
    }

    /**
     * @return string
     */
    public function getLogoUrl(): string
    {
        return $this->assetRepository->getUrl('Idenfy_CustomerVerification::images/idenfy-logo.svg');
    }

    /**
     * @return string
     */
    public function getCartUrl(): string
    {
        return $this->urlBuilder->getUrl('checkout/cart');
    }

    /**
     * Options of the Idenfy_CustomerVerification/js/verification-modal component
     *
     * @return string
     */
    public function getJsConfig(): string
    {
        return $this->jsonSerializer->serialize([
            'mode' => $this->config->getIntegrationMode(),
            'autoOpen' => $this->config->isAutoOpen(),
            'closable' => $this->isClosable(),
            'uiOrigin' => $this->config->getUiOrigin(),
            'statusUrl' => $this->serviceUrl->evaluate(['path' => '/idenfy/verification-status']),
            'startUrl' => $this->serviceUrl->evaluate(['path' => '/idenfy/get-redirect-url']),
            'returnParam' => GetAuthToken::RETURN_QUERY_PARAM,
            // Lets the modal recognise an order that the server rejected, whichever checkout submitted it
            'orderRejectedMessage' => (string) PreventUnverifiedOrder::getMessage(),
            'buttonStyle' => (object) $this->buttonStyle->getCssVariables(),
        ]);
    }
}
