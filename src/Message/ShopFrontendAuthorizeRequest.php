<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, classic payment page (user is sent to
 * the PAYONE site).
 */

use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Payone\ShopFrontendGateway;
use Omnipay\Common\ItemBag;

class ShopFrontendAuthorizeRequest extends ShopAuthorizeRequest
{
    /**
     * Required access method to the ONEPAY credit card form.
     */
    const ACCESS_METHOD_CLASSIC = 'classic';
    const ACCESS_METHOD_IFRAME = 'iframe';

    /**
     * Default values for the auto-created Item if none are supplied.
     */
    protected $defaultItemId = '000000';
    protected $defaultItemDescription = 'Items';

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     * TODO: this is where we need to validate, to make sure we have all
     * required fields present.
     */
    public function getData()
    {
        // The base data.
        $data = [
            'portalid' => $this->getPortalId(),
            'aid' => $this->getSubAccountId(),
            'mode' => $this->getTestMode()
                ? ShopFrontendGateway::MODE_TEST
                : ShopFrontendGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'clearingtype' => $this->getClearingType(),
            'reference' => $this->getTransactionId(),
            'amount' => $this->getAmountInteger(),
            'currency' => $this->getCurrency(),
        ];

        // Add basket contents next.
        // It seems that we MUST have at least one item in
        // the cart to be valid.

        $items = $this->getItems();

        if (empty($items) || $items->count() == 0) {
            // No items in the basket, so we will have to make
            // one up.

            $item = new ExtendItem([
                'id' => $this->defaultItemId,
                'price' => $this->getAmountInteger(),
                'quantity' => 1,
                'description' => $this->defaultItemDescription,
                'vat' => 0,
            ]);

            $items = new ItemBag;
            $items->add($item);
        }

        $item_count = 0;

        foreach($items as $item) {
            $item_count++;

            if (method_exists($item, 'getId')) {
                $id = $item->getId();
            } else {
                $id = $this->defaultItemId;
            }

            if (method_exists($item, 'getVat')) {
                $vat = $item->getVat();
            } else {
                $vat = 0;
            }

            // We are ASSUMING here that the price is in minor units.
            // Since there is no validation or parsing of the Item
            // price, we really cannot know for sure whether it contains
            // €100 or 100c

            $data['id['.$item_count.']'] = $id;
            $data['pr['.$item_count.']'] = $item->getPrice();
            $data['no['.$item_count.']'] = $item->getQuantity();
            $data['de['.$item_count.']'] = $item->getDescription();
            $data['va['.$item_count.']'] = $vat;
        }

        // Create the hash.
        // First we sort the parameters into alphabetic name order.

        $sorted = $data;
        ksort($sorted);

        // Then concatenate the values and add the hash.

        $data['hash'] = $this->hashString(implode('', $sorted), $this->getPortalKey());

        return $data;
    }

    /**
     * The response to sending the request is a text list of name=value pairs.
     * The output data is a mix of the sent data with the received data appended.
     */
    public function sendData($data)
    {
        return $this->createResponse($data);
    }

    /**
     * There are a number of options in accessing the credit card form, including
     * a GET redirect and a POST to an iframe.
     */
    protected function createResponse($data)
    {
        if ($this->getAccessMethod() == static::ACCESS_METHOD_CLASSIC) {
            // Classic redirect (GET, send user to remote site).
            return $this->response = new ShopFrontendClassicAuthorizeResponse($this, $data);
        }

        if ($this->getAccessMethod() == static::ACCESS_METHOD_IFRAME) {
            // Classic redirect (GET, send user to remote site).
            return $this->response = new ShopFrontendIframeAuthorizeResponse($this, $data);
        }

        // TODO: throw exception.
    }

    public function setAccessMethod($value)
    {
        $this->setParameter('accessMethod', $value);
    }

    public function getAccessMethod()
    {
        return $this->getParameter('accessMethod') ?: static::ACCESS_METHOD_CLASSIC;
    }
}
