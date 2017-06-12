<?php

namespace Omnipay\Payone\Message;

/**
 * Authorize, shop mode, classic payment page (user is sent to
 * the PAYONE site).
 */

use Omnipay\Payone\Extend\ItemInterface as ExtendItemInterface;
use Omnipay\Payone\Extend\Item as ExtendItem;
use Omnipay\Payone\AbstractShopGateway;
use Omnipay\Common\ItemBag;

class ShopFrontendAuthorizeRequest extends ShopServerAuthorizeRequest
{
    /**
     * Required access method to the ONEPAY credit card form.
     */
    const ACCESS_METHOD_CLASSIC = 'classic';
    const ACCESS_METHOD_IFRAME = 'iframe';

    /**
     * Redirect method.
     */
    const REDIRECT_METHOD_POST = 'POST';
    const REDIRECT_METHOD_GET = 'GET';

    const ENDPOINT_CLASSIC = 'https://secure.pay1.de/frontend/';
    const ENDPOINT_IFRAME = 'https://frontend.pay1.de/frontend/v2/';

    /**
     * Permitted values for targetWindow.
     * This is for breaking out of the iframe.
     */
    const TARGET_WINDOW_WINDOW  = 'window';
    const TARGET_WINDOW_OPENER  = 'opener';
    const TARGET_WINDOW_TOP     = 'top';
    const TARGET_WINDOW_PARENT  = 'parent';
    const TARGET_WINDOW_BLANK   = 'blank';
    const TARGET_WINDOW_SELF    = 'self';

    /**
     * The display_name values.
     */
    const DISPLAY_NAME_YES = 'yes';
    const DISPLAY_NAME_NO = 'no';

    /**
     * The display_address values.
     */
    const DISPLAY_ADDRESS_YES = 'yes';
    const DISPLAY_ADDRESS_NO = 'no';

    /**
     * The autosubmit values.
     */
    const AUTOSUBMIT_YES = 'yes';
    const AUTOSUBMIT_NO = 'no';

    /**
     * Base data required for all Front End transactions.
     */
    protected function getBaseData()
    {
        $data = array(
            'portalid' => $this->getPortalId(),
            'api_version' => AbstractShopGateway::API_VERSION,
            'aid' => $this->getSubAccountId(),
            'mode' => (bool)$this->getTestMode()
                ? AbstractShopGateway::MODE_TEST
                : AbstractShopGateway::MODE_LIVE,
            'request' => $this->getRequestCode(),
            'clearingtype' => $this->getClearingType(),
            'reference' => $this->getTransactionId(),
            'amount' => $this->getAmountInteger(),
            'currency' => $this->getCurrency(),
            'encoding' => $this->getEncoding(),
        );

        return $data;
    }

    /**
     * The data is used to generate the POST form to send the user
     * off to the PAYONE credit card form.
     */
    public function getData()
    {
        // The base data.
        $data = $this->getBaseData();

        // Add basket contents next.
        // It seems that we MUST have at least one item in
        // the cart to be valid.

        $items = $this->getItems();

        if (empty($items) || $items->count() == 0) {
            // No items in the basket, so we will have to make
            // one up. The Frontend API MUST have at least one cart item.
            // The basket MUST add up to the total payment amount, so
            // be aware of that.

            $item = new ExtendItem([
                'id' => $this->defaultItemId,
                'price' => $this->getAmountInteger(),
                'quantity' => 1,
                'name' => $this->getDescription(),
                'vat' => null,
            ]);

            $items = new ItemBag;
            $items->add($item);

            // Add this dummy cart to the gateway cart.
            $this->setItems($items);
        }

        // Add the cart items to the data.
        $data += $this->getDataItems();

        if ($this->getDisplayName()) {
            $data['display_name'] = $this->getDisplayName();
        }

        if ($this->getDisplayAddress()) {
            $data['display_address'] = $this->getDisplayAddress();
        }

        if ($this->getInvoiceId()) {
            $data['invoiceid'] = $this->getInvoiceId();
        }

        // The errorurl does not appear in the Frontend documentation, but does
        // work and is implemented in other platform gateways.

        $data += $this->getDataUrl();

        if ($this->getTargetWindow()) {
            $data['targetwindow'] = $this->getTargetWindow();
        }

        if ($this->getParam() !== null) {
            $data['param'] = $this->getParam();
        }

        if ($this->getDescription()) {
            $data['narrative_text'] = $this->getDescription();
        }

        $data += $this->getDataPersonal();
        $data += $this->getDataShipping();

        if ($this->getLanguage()) {
            $data['language'] = $this->getLanguage();
        }

        if ($this->getWalletType()) {
            $data['wallettype'] = $this->getWalletType();
        }

        if ($this->getAutosubmit()) {
            $data['autosubmit'] = $this->getAutosubmit();
        }

        if ($this->getFinancingtype()) {
            $data['financingtype'] = $this->getFinancingtype();
        }

        // Create the hash for hashable fields.
        $data['hash'] = $this->hashArray($data);

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
        $this->response = new ShopFrontendAuthorizeResponse($this, $data);

        if ($this->getAccessMethod() == static::ACCESS_METHOD_CLASSIC) {
            $this->response->setEndpoint(static::ENDPOINT_CLASSIC);
        }

        if ($this->getAccessMethod() == static::ACCESS_METHOD_IFRAME) {
            $this->response->setEndpoint(static::ENDPOINT_IFRAME);
        }

        $this->response->setRedirectMethod($this->getRedirectMethod());

        return $this->response;
    }

    /**
     * Access method: classic or iframe
     */
    public function setAccessMethod($value)
    {
        $this->setParameter('accessMethod', $value);
    }

    public function getAccessMethod()
    {
        return $this->getParameter('accessMethod') ?: static::ACCESS_METHOD_CLASSIC;
    }

    /**
     * Indicates whether to display the firstname/lastname/company name fields in the
     * hosted form.
     * Values are "yes" and "no".
     */
    public function setDisplayName($value)
    {
        if ($value === true) {
            $value = static::DISPLAY_NAME_YES;
        } elseif ($value === false) {
            $value = static::DISPLAY_NAME_NO;
        }

        $this->setParameter('displayName', $value);
    }

    public function getDisplayName()
    {
        return $this->getParameter('displayName');
    }

    /**
     * Indicates whether to display the address fields in the
     * hosted form.
     * Values are "yes" and "no".
     */
    public function setDisplayAddress($value)
    {
        if ($value === true) {
            $value = static::DISPLAY_ADDRESS_YES;
        } elseif ($value === false) {
            $value = static::DISPLAY_ADDRESS_NO;
        }

        $this->setParameter('displayAddress', $value);
    }

    public function getDisplayAddress()
    {
        return $this->getParameter('displayAddress');
    }

    /**
     * Indicates whether the form should be autosubmitted and take the
     * use direct to the wallet merchant.
     * Values are "yes" and "no".
     */
    public function setAutosubmit($value)
    {
        if ($value === true) {
            $value = static::AUTOSUBMIT_YES;
        } elseif ($value === false) {
            $value = AUTOSUBMIT_NO;
        }

        $this->setParameter('autosubmit', $value);
    }

    public function getAutosubmit()
    {
        return $this->getParameter('autosubmit');
    }

    /**
     * The target window for breaking out of the iframe at the end.
     * See constants static::TARGET_WINDOW_* for permitted values.
     * Defaults to 'window'.
     */
    public function setTargetWindow($value)
    {
        $this->setParameter('targetWindow', $value);
    }

    public function getTargetWindow()
    {
        return $this->getParameter('targetWindow');
    }

    /**
     * Redirect method: GET or POST.
     */
    public function setRedirectMethod($value)
    {
        if ($value != static::REDIRECT_METHOD_GET && $value != static::REDIRECT_METHOD_POST) {
            // TODO: exception (just default it for now).
            $value = static::REDIRECT_METHOD_GET;
        }

        $this->setParameter('redirectMethod', $value);
    }

    public function getRedirectMethod()
    {
        return $this->getParameter('redirectMethod') ?: 'GET';
    }
}
