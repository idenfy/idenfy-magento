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
    public const CONFIG_PATH_ENABLED = 'idenfy/general/enabled';
    public const CONFIG_PATH_WEBHOOK_SIGNING_KEY = 'idenfy/general/webhook_signing_key';

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
     * Read a website scoped config value, normalising empty values to null
     *
     * @param string $path
     * @return string|null
     */
    private function getStringValue(string $path): ?string
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
