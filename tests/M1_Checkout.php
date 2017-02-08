<?php

require_once 'bootstrap.php';

use MP\Fixtures\App\Mage;

class Checkout extends MP\Sauce\WebDriverTestCase
{
    use Mage;

    protected $isModuleActive   = false;
    protected $defaultConfig    = [];

    const SETTING_GROUP_STYLE           = 'styles';
    const SETTING_GROUP_VERIFICATION    = 'verification';

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

    }

    /**
     *
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

        sleep(30);

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
//            [
//                [
//                    [
//                        'group' => self::SETTING_GROUP_STYLE,
//                        'field' => 'layout',
//                        'value' => self::LAYOUT_MULTI_STEP
//                    ],
//                    [
//                        'group' => self::SETTING_GROUP_VERIFICATION,
//                        'field' => 'active',
//                        'value' => 1
//                    ]
//                ],
//                ['isVerified' => false],
//                'checkMultiSameShipping'
//            ],
//            [
//                [],
//                ['isVerified' => true],
//                'checkMultiSameShipping',
//            ],
            [
                [],
                ['isVerified' => false],
                'checkMultiBilling',
            ],
//            [
//                [],
//                ['isVerified' => true],
//                'checkMultiBilling',
//            ],
//            [
//                [],
//                ['isVerified' => false],
//                'checkMultiShipping',
//            ],
//            [
//                [],
//                ['isVerified' => true],
//                'checkMultiShipping',
//            ],
//            [
//                [],
//                [],
//                'checkMultiBadCard',
//            ],
//            [
//                [],
//                [],
//                'checkMultiCheckout',
//            ],
        ];
    }

    protected function setMultiCustomer()
    {
        $this->byId('firstname')->value('TestName');
        $this->byId('lastname')->value('TestLastName');
        $this->byId('account_email')->value(uniqid('test', true) . '@example.com');

        $this->byId('step_1_1_submit')
            ->click();
    }

    protected function setMultiPayment($isBadCard = false)
    {
        $this->byId('cryozonic_stripe_cc_owner')->value('Test Card');
        $this->byId('cryozonic_stripe_cc_number')->value($isBadCard ? '4000000000000002' : '4242424242424242');
        $this->select($this->byId('cryozonic_stripe_expiration'))
            ->selectOptionByValue(1);
        $this->select($this->byId('cryozonic_stripe_expiration_yr'))
            ->selectOptionByValue(2020);
        $this->byId('cryozonic_stripe_cc_cid')->value('555');
    }

    protected function setMultiShippingAddress($isPostalBad = true)
    {
        $this->byId('shipping:street1')->value('1250 N Us Highway 1');

        $this->select($this->byId('shipping:region_id'))
            ->selectOptionByLabel('Florida');

        $this->byId('shipping:city')->value('Pompano Beach');
        $this->byId('shipping:postcode')->value($isPostalBad ? '33061' : '33062-3705');

        $this->byId('shipping:telephone')->value('5555555555');

        $this->byXPath('//*[@id="shipping_address_form"]/div[2]/button[2]')
            ->click();
    }

    /**
     * Set billing address for multi-page checkout
     *
     *
     * @param bool $isPostalBad
     * @param bool $isSame
     */
    protected function setMultiBillingAddress($isPostalBad = true, $isSame = true)
    {
        // Set address
        $this->byId('billing:street1')->value('Houston Street');

        sleep(10);

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
        }

        $this->byXPath('//*[@id="billing_address_form"]/div[2]/button[2]')
            ->click();
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with bad postcode
     * 3. Shipping same as billing
     * 4. Select verified or not billing address
     *
     * @param array $params
     */
    protected function checkMultiSameShipping(array $params = [])
    {
        // Set customer info
        $this->setMultiCustomer();

        sleep(4);

        // Set Billing Address
        $this->setMultiBillingAddress(true, true);

        sleep(10);

        // Unverified Billing Address
        $this->assertContains(
            'Unverified Billing Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(40);

        $this->setMultiPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with bad postcode
     * 3. Select verified or not billing address
     * 4. Set shipping address with good postcode
     *
     * @param array $params
     */
    protected function checkMultiBilling(array $params = [])
    {
        // Set customer info
        $this->setMultiCustomer();

        sleep(4);

        // Set Billing Address
        $this->setMultiBillingAddress(true, false);

        sleep(10);

        // Unverified Billing Address
        $this->assertContains(
            'Unverified Billing Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(20);

        // Shipping Address
        $this->assertContains(
            'Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        $this->setMultiShippingAddress(false);

        sleep(40);

        $this->assertContains(
            'Payment',
            $this->byXPath('//*[@id="left_content"]/div[1]/h2')->text()
        );

        $this->setMultiPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();
    }

    /**
     * Testing multi-step checkout
     *
     * 1. Set customer info
     * 2. Set billing address with good postcode
     * 3. Set shipping address with bad postcode
     * 4. Select verified or not shipping address
     *
     * @param array $params
     */
    protected function checkMultiShipping(array $params = [])
    {
        // Set customer info
        $this->setMultiCustomer();

        sleep(4);

        // Set Billing Address
        $this->setMultiBillingAddress(false, false);

        sleep(20);

        // Shipping Address
        $this->assertContains(
            'Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        $this->setMultiShippingAddress(true);

        sleep(20);

        // Unverified Shipping Address
        $this->assertContains(
            'Unverified Shipping Address',
            $this->byXPath('//*[@id="left_content"]/div/h2')->text()
        );

        if (!empty($params['isVerified'])) {
            $this->byId('verification1')
                ->click();
        }

        $this->byXPath('//*[@id="shipping_address_suggestion_form"]/div[2]/button[2]')
            ->click();

        sleep(40);

        $this->assertContains(
            'Payment',
            $this->byXPath('//*[@id="left_content"]/div[1]/h2')->text()
        );

        $this->setMultiPayment();

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();
    }

    /**
     * Test checkout with wrong card number for multi-step checkout
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
        $this->setMultiCustomer();

        sleep(4);

        // Set Billing Address
        $this->setMultiBillingAddress(false, true);

        sleep(20);

        $this->setMultiPayment(true);

        // Submit order
        $this->byXPath('//*[@id="payment_form"]/button[2]')
            ->click();

        sleep(20);

        $this->assertContains(
            'Your card was declined',
            $this->byXPath('//*[@id="checkout-content"]/div[1]')->text()
        );
    }

    /**
     * Add product to cart
     * Redirect to checkout page
     */
    protected function addProduct()
    {
        $this->url('/');
        sleep(2);

        // Validate Home Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('cms-index-index', $body->attribute('class'));

        // Purchase by homepage
        $this->byCssSelector('#home-intro .btn-buy')->click();

        // Validate Product Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('catalog-product-view', $body->attribute('class'));

        // Purchase by product page
        $this->byCssSelector('#product_addtocart_form #product-shop .btn-buy')->click();
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
     * Leaves performance critical cache (configuration, ddl) untouched.
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