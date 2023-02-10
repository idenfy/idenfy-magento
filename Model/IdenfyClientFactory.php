<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientFactory as GuzzleClientFactory;
use Magento\Framework\Exception\LocalizedException;

class IdenfyClientFactory
{
    /** @var GuzzleClientFactory */
    private GuzzleClientFactory $guzzleClientFactory;

    /** @var Config  */
    private Config $config;

    /**
     * @param GuzzleClientFactory $guzzleClientFactory
     * @param Config $config
     */
    public function __construct(
        GuzzleClientFactory $guzzleClientFactory,
        Config $config
    ) {
        $this->guzzleClientFactory = $guzzleClientFactory;
        $this->config = $config;
    }

    /**
     * @return GuzzleClient
     * @throws LocalizedException
     */
    public function create(): GuzzleClient
    {
        $apiKey = $this->config->getApiKey();
        $apiSecret = $this->config->getApiSecret();

        if (!$apiKey || !$apiSecret) {
            throw new LocalizedException(__('Unable to create Idenfy Client. Missing API Key or API Secret'));
        }

        $config = [
            'base_uri' => rtrim($this->config->getApiBaseUrl(), '/') . '/',
            'headers' => [
                'Cache-Control' => 'nocache',
                'Content-Type' => 'application/json',
                'Authorization'=> 'Basic ' . base64_encode($apiKey . ':' . $apiSecret)
            ],
        ];

        return $this->guzzleClientFactory->create(['config' => $config]);
    }
}
