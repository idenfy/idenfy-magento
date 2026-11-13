<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{

    public const CONFIG_PATH_API_KEY = 'idenfy/general/api_key';
    public const CONFIG_PATH_API_SECRET = 'idenfy/general/api_secret';
    public const CONFIG_PATH_API_BASE_URL = 'idenfy/general/api_base_url';
    public const CONFIG_PATH_UI_BASE_URL = 'idenfy/general/ui_base_url';
    public const CONFIG_PATH_SESSION_REUSE_LIFETIME = 'idenfy/general/session_reuse_lifetime';
    public const CONFIG_PATH_ENABLED = 'idenfy/general/enabled';
    public const CONFIG_PATH_WEBHOOK_SIGNING_KEY = 'idenfy/general/webhook_signing_key';

    public const CONFIG_PATH_INTEGRATION_MODE = 'idenfy/storefront/integration_mode';
    public const CONFIG_PATH_AUTO_OPEN = 'idenfy/storefront/auto_open';
    public const CONFIG_PATH_CLOSABLE = 'idenfy/storefront/closable';
    public const CONFIG_PATH_THEME_ID = 'idenfy/storefront/theme_id';
    public const CONFIG_PATH_MODAL_TITLE = 'idenfy/storefront/modal_title';
    public const CONFIG_PATH_MODAL_TEXT = 'idenfy/storefront/modal_text';
    public const CONFIG_PATH_BUTTON_LABEL = 'idenfy/storefront/button_label';
    public const CONFIG_PATH_BUTTON_STYLE = 'idenfy/storefront/button_style';
    public const CONFIG_PATH_BUTTON_BACKGROUND_COLOR = 'idenfy/storefront/button_background_color';
    public const CONFIG_PATH_BUTTON_TEXT_COLOR = 'idenfy/storefront/button_text_color';
    public const CONFIG_PATH_BUTTON_BORDER_RADIUS = 'idenfy/storefront/button_border_radius';
    public const CONFIG_PATH_ADDITIONAL_CHECKOUT_PAGES = 'idenfy/storefront/additional_checkout_pages';

    public const MODE_IFRAME = 'iframe';
    public const MODE_REDIRECT = 'redirect';

    public const BUTTON_STYLE_THEME = 'theme';
    public const BUTTON_STYLE_CUSTOM = 'custom';

    public const DEFAULT_UI_BASE_URL = 'https://ui.idenfy.com';
    public const MAX_BUTTON_BORDER_RADIUS = 50;

    /** Accepted hex colours: #RGB or #RRGGBB */
    public const HEX_COLOR_PATTERN = '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i';

    /** @var ScopeConfigInterface  */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->getStringValue(self::CONFIG_PATH_API_KEY);
    }

    /**
     * @return string|null
     */
    public function getApiSecret(): ?string
    {
        return $this->getStringValue(self::CONFIG_PATH_API_SECRET);
    }

    /**
     * Base URL of the Idenfy API, without a trailing slash
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return rtrim((string) $this->getStringValue(self::CONFIG_PATH_API_BASE_URL), '/');
    }

    /**
     * Base URL of the verification UI that is embedded in the iFrame, without a trailing slash
     *
     * @return string
     */
    public function getUiBaseUrl(): string
    {
        return rtrim($this->getStringValue(self::CONFIG_PATH_UI_BASE_URL) ?? self::DEFAULT_UI_BASE_URL, '/');
    }

    /**
     * Origin of the verification UI, used to check that messages posted to the checkout come from the iFrame
     *
     * @return string
     */
    public function getUiOrigin(): string
    {
        $parts = parse_url($this->getUiBaseUrl());

        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return self::DEFAULT_UI_BASE_URL;
        }

        return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    /**
     * Seconds during which an unfinished verification session is reused instead of creating a new one
     *
     * @return int
     */
    public function getSessionReuseLifetime(): int
    {
        return max(0, (int) $this->getStringValue(self::CONFIG_PATH_SESSION_REUSE_LIFETIME));
    }

    /**
     * Signing key configured for the webhook in the Idenfy dashboard, if any
     *
     * @return string|null
     */
    public function getWebhookSigningKey(): ?string
    {
        return $this->getStringValue(self::CONFIG_PATH_WEBHOOK_SIGNING_KEY);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (!$this->scopeConfig->isSetFlag(self::CONFIG_PATH_ENABLED, ScopeInterface::SCOPE_WEBSITE)) {
            return false;
        }

        return $this->getApiKey() !== null && $this->getApiSecret() !== null;
    }

    /**
     * How the verification is shown to the customer: embedded in an iFrame (default) or by redirecting to Idenfy
     *
     * @return string
     */
    public function getIntegrationMode(): string
    {
        return $this->getStringValue(self::CONFIG_PATH_INTEGRATION_MODE) === self::MODE_REDIRECT
            ? self::MODE_REDIRECT
            : self::MODE_IFRAME;
    }

    /**
     * @return bool
     */
    public function isIframeMode(): bool
    {
        return $this->getIntegrationMode() === self::MODE_IFRAME;
    }

    /**
     * Whether the verification modal opens as soon as an unverified customer arrives on the checkout
     *
     * @return bool
     */
    public function isAutoOpen(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_AUTO_OPEN, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * Whether the customer can dismiss the verification modal. A modal the customer opened is always closable.
     *
     * @return bool
     */
    public function isModalClosable(): bool
    {
        return !$this->isAutoOpen()
            || $this->scopeConfig->isSetFlag(self::CONFIG_PATH_CLOSABLE, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * ID of the Idenfy KYC branding theme to apply to new sessions, if any
     *
     * @return string|null
     */
    public function getThemeId(): ?string
    {
        $themeId = $this->getStringValue(self::CONFIG_PATH_THEME_ID);

        return $themeId !== null && preg_match('/^[A-Za-z0-9-]{1,64}$/', $themeId) ? $themeId : null;
    }

    /**
     * Title of the verification modal for the current store view
     *
     * @return string
     */
    public function getModalTitle(): string
    {
        return $this->getStringValue(self::CONFIG_PATH_MODAL_TITLE, ScopeInterface::SCOPE_STORE)
            ?? (string) __('Verify your identity');
    }

    /**
     * Introduction text of the verification modal for the current store view
     *
     * @return string
     */
    public function getModalText(): string
    {
        return $this->getStringValue(self::CONFIG_PATH_MODAL_TEXT, ScopeInterface::SCOPE_STORE)
            ?? (string) __(
                'To complete your purchase, we need to quickly verify your identity. '
                . 'It is secure and only takes a few minutes.'
            );
    }

    /**
     * Label of the button that starts the verification, for the current store view
     *
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->getStringValue(self::CONFIG_PATH_BUTTON_LABEL, ScopeInterface::SCOPE_STORE)
            ?? (string) __('Start verification');
    }

    /**
     * @return bool
     */
    public function isCustomButtonStyle(): bool
    {
        return $this->getStringValue(self::CONFIG_PATH_BUTTON_STYLE, ScopeInterface::SCOPE_STORE)
            === self::BUTTON_STYLE_CUSTOM;
    }

    /**
     * Custom button background colour, or null to keep the theme colour
     *
     * @return string|null
     */
    public function getButtonBackgroundColor(): ?string
    {
        return $this->getHexColor(self::CONFIG_PATH_BUTTON_BACKGROUND_COLOR);
    }

    /**
     * Custom button text colour, or null to keep the theme colour
     *
     * @return string|null
     */
    public function getButtonTextColor(): ?string
    {
        return $this->getHexColor(self::CONFIG_PATH_BUTTON_TEXT_COLOR);
    }

    /**
     * Custom button corner radius in pixels, or null to keep the theme radius
     *
     * @return int|null
     */
    public function getButtonBorderRadius(): ?int
    {
        $radius = $this->getStringValue(self::CONFIG_PATH_BUTTON_BORDER_RADIUS, ScopeInterface::SCOPE_STORE);

        if ($radius === null || !ctype_digit($radius)) {
            return null;
        }

        return min((int) $radius, self::MAX_BUTTON_BORDER_RADIUS);
    }

    /**
     * Full action names of additional checkout pages (e.g. of one-step checkout extensions) to guard
     *
     * @return string[]
     */
    public function getAdditionalCheckoutPages(): array
    {
        $value = (string) $this->getStringValue(self::CONFIG_PATH_ADDITIONAL_CHECKOUT_PAGES);
        $pages = preg_split('/[\s,]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($pages ?: []));
    }

    /**
     * @param string $path
     * @return string|null
     */
    private function getHexColor(string $path): ?string
    {
        $color = $this->getStringValue($path, ScopeInterface::SCOPE_STORE);

        return $color !== null && preg_match(self::HEX_COLOR_PATTERN, $color) ? $color : null;
    }

    /**
     * Read a config value of the current website (or store view), normalising empty values to null
     *
     * @param string $path
     * @param string $scope
     * @return string|null
     */
    private function getStringValue(string $path, string $scope = ScopeInterface::SCOPE_WEBSITE): ?string
    {
        $value = $this->scopeConfig->getValue($path, $scope);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
