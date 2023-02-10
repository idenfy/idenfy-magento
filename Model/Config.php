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
        return $this->scopeConfig->getValue(self::CONFIG_PATH_API_KEY, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string|null
     */
    public function getApiSecret(): ?string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_API_SECRET, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_API_BASE_URL, ScopeInterface::SCOPE_WEBSITE);
    }
}
