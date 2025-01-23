<?php

declare(strict_types=1);

namespace Rapidez\StandaloneCheckout\Plugin\Magento\Checkout\Controller\Index;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteId;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResourceModel;

class Index
{
    public function __construct(
        private CheckoutSession $checkoutSession,
        private CustomerSession $customerSession,
        private QuoteIdToMaskedQuoteId $quoteIdToMaskedQuoteId,
        private QuoteIdMaskFactory $quoteIdMaskFactory,
        private QuoteIdMaskResourceModel $quoteIdMaskResourceModel,
        private TokenFactory $tokenFactory,
        private ClientFactory $clientFactory,
        private ResultFactory $resultFactory,
        private ScopeConfigInterface $scopeConfig
    ) {}

    public function getRapidezUrl(): ?string
    {
        return $this->scopeConfig->getValue(
            'standalone_checkout/general/rapidez_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
         );
    }

    public function getQuoteHash(): ?string
    {
        $quoteId = (int)$this->checkoutSession->getQuote()->getId();

        if(!$quoteId) {
            return null;
        }

        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (NoSuchEntityException $e) {
            $maskedId = '';
        }
        if ($maskedId === '') {
            /** @var QuoteIdMask $quoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            $quoteIdMask->setQuoteId($quoteId);
            $this->quoteIdMaskResourceModel->save($quoteIdMask);

            return $quoteIdMask->getMaskedId();
        }

        return $maskedId;
    }

    public function getCustomerToken(): ?string
    {
        $customerId = (int)$this->customerSession->getCustomer()->getId();

        if(!$customerId) {
            return null;
        }

        return $this->tokenFactory->create()->createCustomerToken($customerId)->getToken();
    }

    public function aroundExecute(
        \Magento\Checkout\Controller\Index\Index $subject,
        \Closure $proceed
    ) {
        $rapidezUrl = $this->getRapidezUrl();
        $mask = $this->getQuoteHash();
        $token = $this->getCustomerToken();
        if(!$rapidezUrl || !$mask) {
            return $proceed();
        }

        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $rapidezUrl
        ]]);

        $response = $client->request(
            'POST',
            '/api/get-checkout-url',
            [
                'json' => [
                    'mask' => $mask,
                    'token' => $token,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        /** @var ?string $url */
        $url = json_decode((string)$response->getBody(), false)?->url;
        if ($url) {
            /** @var Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setUrl($url);

            return $redirect;
        }

        return $proceed();
    }
}

