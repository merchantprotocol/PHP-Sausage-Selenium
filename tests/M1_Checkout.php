<?php

require_once 'bootstrap.php';

use MP\Fixtures\App\Mage;

/**
 * TODO: replace sleep by a more optimal function for ajax call
 *
 * Class MpCheckout
 */
class MpCheckout extends MP\Sauce\WebDriverTestCase
{
    use Mage;

    protected $isModuleActive   = false;
    protected $defaultConfig    = [];

    const SETTING_GROUP_STYLE           = 'styles';
    const SETTING_GROUP_VERIFICATION    = 'verification';
    const SETTING_GROUP_AUTO_COMPETE    = 'autocomplete';
    const SETTING_GROUP_AUTO_UP_SELL    = 'upsell';

    const LAYOUT_ONE_STEP   = 2;
    const LAYOUT_MULTI_STEP = 1;

    /**
     * Checkout constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Get initialized application object
        $this->initMage();

        $this->isModuleActive = $this->isModuleActive();
    }

    /**
     * Get extension settings before start tests
     */
    public function setUp()
    {
        parent::setUp();

        $this->defaultConfig = \Mage::getStoreConfig('mp_checkout');
    }

    /**
     * Restore default settings after tests
     */
    public function tearDown()
    {
        parent::tearDown();

        if (empty($this->defaultConfig)) return;

        foreach($this->defaultConfig as $index => $group) {
            foreach($group as $field => $value) {
                \Mage::getConfig()->saveConfig(
                    "mp_checkout/{$index}/{$field}",
                    $value,
                    'default'
                );
            }
        }
    }

    /**
     * Testing MP Checkout extension functionality
     *
     * 1. Getting from data provider arguments: settings, method params, method
     * 2. Call method for check
     *
     * @param array $params
     * @param array $methodParams
     * @param string $method
     *
     * @dataProvider settingProvider
     */
    public function testCheckout(array $params = [], array $methodParams = [], $method = '')
    {
        if (!$this->isModuleActive) {
            $this->markTestSkipped('Extension MP_Checkout is not active.');
        }

        // Set configuration for layout
        if (!empty($params)) {
            $this->setCheckoutConfig($params);
        }

        // Add product
        $this->addProduct();

        sleep(5);

        if (method_exists($this, $method)) {
            $this->$method($methodParams);
        }
    }

    /**
     * Data provider. Provides settings and methods for test
     * [["settings", "methodParams", "method"]]
     *
     * @return array
     */
    public function settingProvider()
    {
        return [
            [
                [
                    [
                        'group' => self::SETTING_GROUP_STYLE,
                        'field' => 'layout',
                        'value' => self::LAYOUT_MULTI_STEP
                    ],
                    [
                        'group' => self::SETTING_GROUP_VERIFICATION,
                        'field' => 'active',
                        'value' => 1
                    ],
                    [
                        'group' => self::SETTING_GROUP_STYLE,
                        'field' => 'request_names',
                        'value' => self::LAYOUT_MULTI_STEP
                    ],
                    [
                        'group' => self::SETTING_GROUP_AUTO_COMPETE,
                        'field' => 'active',
                        'value' => 1
                    ],
                    [
                        'group' => self::SETTING_GROUP_AUTO_UP_SELL,
                        'field' => 'active',
                        'value' => 0
                    ]
                ],
                ['isVerified' => false],
                'checkMultiSameShipping'
            ],
            [
                [],
                ['isVerified' => true],
                'checkMultiSameShipping',
            ],
            [
                [],
                ['isVerified' => false],
                'checkMultiBilling',
            ],
            [
                [],
                ['isVerified' => true],
                'checkMultiBilling',
            ],
            [
                [],
                ['isVerified' => false],
                'checkMultiShipping',
            ],
            [
                [],
                ['isVerified' => true],
                'checkMultiShipping',
            ],
            [
                [],
                [],
                'checkMultiBadCard',
            ],
            [
                [],
                ['isNewCustomer' => true],
                'checkMultiCheckout',
            ],
            [
                [],
                ['isNewCustomer' => false],
                'checkMultiCheckout',
            ],
            [
                [
                    [
                        'group' => self::SETTING_GROUP_STYLE,
                        'field' => 'layout',
                        'value' => self::LAYOUT_ONE_STEP
                    ],
                ],
                [],
                'checkOneBadCard',
            ],
            [
                [],
                ['isNewCustomer' => true],
                'checkOneCheckout',
            ],
            [
                [],
                ['isNewCustomer' => false],
                'checkOneCheckout',
            ],
        ];
    }

    /**
     * Set customer info
     *
     * @param bool $isNewCustomer
     * @throws Exception
     */
    protected function setCustomer($isNewCustomer = true)
    {
        $email = uniqid('test', true) . '@example.com';

        if (!$isNewCustomer) {
            $customer   = $this->getTestConfig()->getValue('customer');
            $email      = !empty($customer['login']) ? $customer['login'] : $email;
        }

        $this->byId('firstname')->value('TestName');
        $this->byId('lastname')->value('TestLastName');
        $this->byId('account_email')->value($email);
    }

    /**
     * Set payment info with valid or not card number
     *
     * @param bool $isBadCard
     */
    protected function setPayment($isBadCard = false)
    {
        $this->byId('cryozonic_stripe_cc_owner')->value('Test Card');
        $this->byId('cryozonic_stripe_cc_number')->value($isBadCard ? '4000000000000002' : '4242424242424242');
        $this->select($this->byId('cryozonic_stripe_expiration'))
            ->selectOptionByValue(1);
        $this->select($this->byId('cryozonic_stripe_expiration_yr'))
            ->selectOptionByValue(date('Y', strtotime('+1 year')));
        $this->byId('cryozonic_stripe_cc_cid')->value('555');
    }

    /**
     * Set shipping address
     * With wrong or not postal code
     * Return address for check
     *
     * @param bool $isPostalBad
     * @return array
     */
    protected function setShippingAddress($isPostalBad = true)
    {
        $this->byId('shipping:street1')->value('1250 N Us Highway 1');

        $this->select($this->byId('shipping:region_id'))
            ->selectOptionByLabel('Florida');

        $this->byId('shipping:city')->value('Pompano Beach');
        $this->byId('shipping:postcode')->value($isPostalBad ? '33061' : '33062-3705');

        $this->byId('shipping:telephone')->value('5555555555');

        $address['street'][]    = $this->byId('shipping:street1')->value();
        $address['region_id']   = $this->byId('shipping:region_id')->value();
        $address['city']        = $this->byId('shipping:city')->value();
        $address['postcode']    = $this->byId('shipping:postcode')->value();

        return $address;
    }

    /**
     * Set billing address
     * Select address from Auto-complete Address Suggestions
     * With wrong or not postal code
     * Set shipping same as billing
     * Return address for check
     *
     * @param bool $isPostalBad
     * @param bool $isSame
     * @return array
     */
    protected function setBillingAddress($isPostalBad = true, $isSame = true)
    {
        // Set address
        $this->byId('billing:street1')->value('Houston Street');

        sleep(3);

        // Use keys for select
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::DOWN);
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);

        // Set wrong postal code;
        if ($isPostalBad) {
            $postal     = $this->byId('billing:postcode');
            $postalCode = $postal->value();

            $postal->clear();
            $postal->value(substr($postalCode, 0, -1));
        }

        // Set telephone
        $this->byId('billing:telephone')->value('5555555555');

        // Set different billing and shipping addresses
        if (!$isSame) {
            $this->byId('billing:use_for_shipping_yes')
                ->click();

            sleep(4);
        }

        $address['street'][]    = $this->byId('billing:street1')->value();
        $address['region_id']   = $this->byId('billing:region_id')->value();
        $address['city']        = $this->byId('billing:city')->value();
        $address['postcode']    = $this->byId('billing:postcode')->value();

        return $address;
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with bad postcode
     * 3. Shipping same as billing
     * 4. Select verified or not billing address
     * 5. Submit order
     * 6. Check order address
     *
     * @param array $params
     */
    protected function checkMultiSameShipping(array $params = [])
    {
        // Set customer info
        $this->setCustomer();

        $this->byId('step_1_1_submit')
            ->click();

        sleep(5);

        // Set Billing Address
        $billingAddress = $this->setBillingAddress(true, true);

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Unverified Billing Address
        $this->assertContains(
            'Unverified Billing Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();

            // Get new address from selected variant
            $address    = json_decode($this->byId('verification1')->value(), true);
            $region     = \Mage::getModel('directory/region')->loadByCode($address['PoliticalDivision1'], 'US');

            $billingAddress = [];

            $billingAddress['street'][]    = ucwords(strtolower($address['AddressLine']));
            $billingAddress['region_id']   = $region->getId();
            $billingAddress['city']        = ucwords(strtolower($address['PoliticalDivision2']));
            $billingAddress['postcode']    = $address['PostcodePrimaryLow'] . '-' . $address['PostcodeExtendedLow'];
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Set payment method
        $this->setPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(10);

        // Check Order Address
        $shippingAddress = $billingAddress;

        $this->checkOrderAddress($billingAddress, $shippingAddress);
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with bad postcode
     * 3. Select verified or not billing address
     * 4. Set shipping address with good postcode
     * 5. Submit order
     * 6. Check order address
     *
     * @param array $params
     */
    protected function checkMultiBilling(array $params = [])
    {
        // Set customer info
        $this->setCustomer();

        $this->byId('step_1_1_submit')
            ->click();

        sleep(5);

        // Set Billing Address
        $billingAddress = $this->setBillingAddress(true, false);

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Unverified Billing Address
        $this->assertContains(
            'Unverified Billing Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();

            // Get new address from selected variant
            $address    = json_decode($this->byId('verification1')->value(), true);
            $region     = \Mage::getModel('directory/region')->loadByCode($address['PoliticalDivision1'], 'US');

            $billingAddress = [];

            $billingAddress['street'][]    = ucwords(strtolower($address['AddressLine']));
            $billingAddress['region_id']   = $region->getId();
            $billingAddress['city']        = ucwords(strtolower($address['PoliticalDivision2']));
            $billingAddress['postcode']    = $address['PostcodePrimaryLow'] . '-' . $address['PostcodeExtendedLow'];
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Shipping Address
        $this->assertContains(
            'Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        $shippingAddress = $this->setShippingAddress(false);

        $this->byXPath('//*[@id="shipping_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        $this->assertContains(
            'Payment',
            $this->byXPath('//*[@id="left_content"]/div[1]/h2')->text()
        );

        // Set payment method
        $this->setPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(10);

        // Check Order Address
        $this->checkOrderAddress($billingAddress, $shippingAddress);
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with good postcode
     * 3. Set shipping address with bad postcode
     * 4. Select verified or not shipping address
     * 5. Submit order
     * 6. Check order address
     *
     * @param array $params
     */
    protected function checkMultiShipping(array $params = [])
    {
        // Set customer info
        $this->setCustomer();

        $this->byId('step_1_1_submit')
            ->click();

        sleep(5);

        // Set Billing Address
        $billingAddress = $this->setBillingAddress(false, false);

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Shipping Address
        $this->assertContains(
            'Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        $shippingAddress = $this->setShippingAddress(true);

        $this->byXPath('//*[@id="shipping_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Unverified Shipping Address
        $this->assertContains(
            'Unverified Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();

            // Get new address from selected variant
            $address    = json_decode($this->byId('verification1')->value(), true);
            $region     = \Mage::getModel('directory/region')->loadByCode($address['PoliticalDivision1'], 'US');

            $shippingAddress = [];

            $shippingAddress['street'][]    = ucwords(strtolower($address['AddressLine']));
            $shippingAddress['region_id']   = $region->getId();
            $shippingAddress['city']        = ucwords(strtolower($address['PoliticalDivision2']));
            $shippingAddress['postcode']    = $address['PostcodePrimaryLow'] . '-' . $address['PostcodeExtendedLow'];
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        $this->assertContains(
            'Payment',
            $this->byXPath('//*[@id="left_content"]/div[1]/h2')->text()
        );

        // Set payment method
        $this->setPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(10);

        // Check Order Address
        $this->checkOrderAddress($billingAddress, $shippingAddress);
    }

    /**
     * Testing checkout with wrong card number for multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address
     * 3. Set payment info
     * 4. Submit order
     * 5. Check card validation
     */
    protected function checkMultiBadCard()
    {
        // Set customer info
        $this->setCustomer();

        $this->byId('step_1_1_submit')
            ->click();

        sleep(5);

        // Set Billing Address
        $this->setBillingAddress(false, true);

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Set payment method
        $this->setPayment(true);

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(10);

        $this->assertContains(
            'Your card was declined',
            $this->byXPath('//*[@id="checkout-content"]/div[1]')->text()
        );
    }

    /**
     * Testing checkout with new or not email for multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address
     * 3. Set payment method
     * 4. Submit order
     * 5. Check order address
     *
     * @param array $params
     */
    protected function checkMultiCheckout($params = array())
    {
        // Set customer info
        $this->setCustomer(!empty($params['isNewCustomer']) ? true : false);

        $this->byId('step_1_1_submit')
            ->click();

        sleep(5);

        // Set Billing Address
        $billingAddress     = $this->setBillingAddress(false, true);
        $shippingAddress    = $billingAddress;

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();

        sleep(5);

        // Set payment method
        $this->setPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(10);

        // Check Order Address
        $this->checkOrderAddress($billingAddress, $shippingAddress);
    }

    /**
     * Testing checkout with wrong card number for one-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address
     * 3. Set payment info
     * 4. Submit order
     * 5. Check card validation
     */
    protected function checkOneBadCard()
    {
        // Set customer
        $this->setCustomer();

        // Set Billing Address
        $this->setBillingAddress(false, true);

        //Set payment
        $this->setPayment(true);

        // Submit order
        $this->byXPath('//*[@id="review_content"]/form/button')
            ->click();

        sleep(10);

        $this->assertContains(
            'Your card was declined',
            $this->byXPath('//*[@id="checkout-content"]/div[1]')->text()
        );
    }

    /**
     * Testing checkout with new or not email for one-step checkout
     * Shipping not same as billing address
     *
     * 1. Set customer info
     * 2. Set billing address
     * 3. Set shipping address
     * 4. Set payment method
     * 5. Submit order
     * 6. Check order address
     *
     * @param array $params
     */
    protected function checkOneCheckout($params = array())
    {
        // Set customer
        $this->setCustomer(!empty($params['isNewCustomer']) ? true : false);

        // Set Billing Address
        $billingAddress = $this->setBillingAddress(false, false);

        // Set Shipping Address
        $this->byId('shipping:firstname')->value('TestName');
        $this->byId('shipping:lastname')->value('TestLastName');

        $shippingAddress = $this->setShippingAddress(false);

        //Set payment
        $this->setPayment();

        // Submit order
        $this->byXPath('//*[@id="review_content"]/form/button')
            ->click();

        sleep(10);

        // Check Order Address
        $this->checkOrderAddress($billingAddress, $shippingAddress);
    }

    /**
     * Assert same for order billing and shipping addresses
     *
     * @param array $billingAddress
     * @param array $shippingAddress
     */
    protected function checkOrderAddress(array $billingAddress, array $shippingAddress)
    {
        $orderText = $this->byXPath('//*[@id="left_content"]/div/div[1]/table/tbody/tr/td/p[2]')->text();

        $this->assertContains(
            'Your order # is',
            $orderText
        );

        $orderNumber = (int)preg_replace('/[^0-9]/', '', $orderText);

        $this->assertNotEmpty($orderNumber);

        $order = \Mage::getModel('sales/order')->loadByIncrementId($orderNumber);

        $this->assertNotEmpty($order->getId());

        $billingOrderAddress = $order->getBillingAddress();
        $shippingOrderAddress = $order->getShippingAddress();

        $this->assertSame($billingAddress['street'], $billingOrderAddress->getStreet());
        $this->assertSame($billingAddress['region_id'], $billingOrderAddress->getRegionId());
        $this->assertSame($billingAddress['city'], $billingOrderAddress->getCity());
        $this->assertSame($billingAddress['postcode'], $billingOrderAddress->getPostcode());

        $this->assertSame($shippingAddress['street'], $shippingOrderAddress->getStreet());
        $this->assertSame($shippingAddress['region_id'], $shippingOrderAddress->getRegionId());
        $this->assertSame($shippingAddress['city'], $shippingOrderAddress->getCity());
        $this->assertSame($shippingAddress['postcode'], $shippingOrderAddress->getPostcode());
    }

    /**
     * Add product to cart
     * Redirect to checkout page
     * 
     * TODO: use fixture instead
     */
    protected function addProduct()
    {
        $this->url('/');
        sleep(2);

        $body = $this->byXPath('//*[@id="current_version"]/strong');
        $this->assertNotNull($body->text());

        // Purchase by homepage
        $this->byCssSelector('#home-intro .btn-buy')->click();

        sleep(4);
        
        // Validate Product Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('catalog-product-view', $body->attribute('class'));

        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);
        
        // Purchase by product page
        $this->byCssSelector('#product-efile-box .btn-buy')->click();
        sleep(2);
    }

    /**
     * Returns true if MP_Checkout is active
     *
     * @return bool
     */
    protected function isModuleActive()
    {
        $activeExtension    = (string)\Mage::getConfig()->getModuleConfig('MP_Checkout')->active;
        $activeCheckout     = (int)\Mage::getStoreConfig('mp_checkout/settings/active');

        return $activeExtension === 'true' && $activeCheckout ? true : false;
    }

    /**
     * Set extension settings for test
     *
     * @param array $settings
     * @return bool
     */
    protected function setCheckoutConfig(array $settings)
    {
        if (empty($settings)) {
            return false;
        }

        foreach($settings as $option) {
            \Mage::getConfig()->saveConfig(
                "mp_checkout/{$option['group']}/{$option['field']}",
                $option['value'],
                'default'
            );
        }

        // Clean cache after settings changed
        $this->cleanupCache();

        return true;
    }

    /**
     * Clean cache
     * Leaves performance critical cache (ddl) untouched.
     */
    protected function cleanupCache()
    {
        \Mage::app()->getCache()->clean(
            \Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG,
            array(
                \Varien_Db_Adapter_Pdo_Mysql::DDL_CACHE_TAG,
            )
        );
    }
}