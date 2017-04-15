<?php

namespace Omnipay\Payone;

use Omnipay\Tests\GatewayTestCase;

class ShopFrontendGatewayTest extends GatewayTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new ShopFrontendGateway($this->getHttpClient(), $this->getHttpRequest());

        $this->options = array(
            'merchantId' => 12345678,
            'subAccountId' => 12345,
            'amount' => '10.00',
            'card' => $this->getValidCard(),
        );

        $this->purchaseOptions = array(
            'amount' => '10.00',
            'currency' => 'GBP',
            'transactionId' => '123',
            'card' => $this->getValidCard(),
        );

        $this->captureOptions = array(
            'amount' => '10.00',
            'transactionId' => '123',
            'transactionReference' => '????',
        );
    }

    /**
     * Generate a random value for a gateway parameter.
     * The value generated will depend on the data type of
     * the default parameter value.
     *
     * @parame mixed $default
     * @return mixed
     */
    protected function makeUnique($default)
    {
        switch (gettype($default)) {
            case 'integer':
                $value = rand(100000, 999999);
                break;

            case 'array':
                $value = $default[array_rand($default)];
                break;

            case 'string':
            default:
                $value = uniqid();
                break;
        }

        return $value;
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testDefaultParametersHaveMatchingMethods()
    {
        $settings = $this->gateway->getDefaultParameters();

        foreach ($settings as $key => $default) {
            $getter = 'get'.ucfirst($key);
            $setter = 'set'.ucfirst($key);
            $value = $this->makeUnique($default);
            $this->assertTrue(method_exists($this->gateway, $getter), "Gateway must implement $getter()");
            $this->assertTrue(method_exists($this->gateway, $setter), "Gateway must implement $setter()");

            // setter must return instance
            $this->assertSame($this->gateway, $this->gateway->$setter($value));
            $this->assertSame($value, $this->gateway->$getter());
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testAuthorizeParameters()
    {
        if ($this->gateway->supportsAuthorize()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->authorize();
                $this->assertSame($value, $request->$getter());
            }
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testCaptureParameters()
    {
        if ($this->gateway->supportsCapture()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->capture();
                $this->assertSame($value, $request->$getter());
            }
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testPurchaseParameters()
    {
        foreach ($this->gateway->getDefaultParameters() as $key => $default) {
            // set property on gateway
            $getter = 'get'.ucfirst($key);
            $setter = 'set'.ucfirst($key);
            $value = $this->makeUnique($default);
            $this->gateway->$setter($value);

            // request should have matching property, with correct value
            $request = $this->gateway->purchase();
            $this->assertSame($value, $request->$getter());
        }
    }

    /**
     * Override parent test.
     * See here for details, and remove when fixed:
     * https://github.com/thephpleague/omnipay-tests/issues/10
     */
    public function testVoidParameters()
    {
        if ($this->gateway->supportsVoid()) {
            foreach ($this->gateway->getDefaultParameters() as $key => $default) {
                // set property on gateway
                $getter = 'get'.ucfirst($key);
                $setter = 'set'.ucfirst($key);
                $value = $this->makeUnique($default);
                $this->gateway->$setter($value);

                // request should have matching property, with correct value
                $request = $this->gateway->void();
                $this->assertSame($value, $request->$getter());
            }
        }
    }

    public function testAuthorizeHashSuccess()
    {
        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        // Success will be false because the transactino is not complete yet - the user
        // needs to be presented with a form.
        $this->assertFalse($response->isSuccessful());

        // The redirect is for redirecting the full page OR into an iframe
        $this->assertTrue($response->isRedirect());

        $this->assertInternalType('array', $response->getRedirectData());

        // TODO: validate the full $response->getRedirectData() array.
    }

    // Note: the following two tests rely on the request data not changing.
    // They may fail if the OmniPay core test data (address, name etc) changes.

    public function testAuthorizeHashMd5()
    {
        $this->gateway->setHashMethod('MD5');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $redirectData = $response->getRedirectData();

        // 
        $this->assertArrayHasKey('hash', $redirectData);

        // An MD5 hash is generated.
        $this->assertSame('d6db6163f94a03997bde9e670cb9fddb', $redirectData['hash']);
    }

    public function testAuthorizeHashSha2()
    {
        $this->gateway->setHashMethod('SHA2_384');

        $response = $this->gateway->authorize($this->purchaseOptions)->send();

        $redirectData = $response->getRedirectData();

        // 
        $this->assertArrayHasKey('hash', $redirectData);

        // An SHA2-384 hash is generated.
        $this->assertSame('7c27ce102dc1be6f59e30ff4ab436cd22d6430dd425544a10c46a88b38ad15fe87ea6ee8cc5c965095dcb279a7e1f0e0', $redirectData['hash']);
    }
}
