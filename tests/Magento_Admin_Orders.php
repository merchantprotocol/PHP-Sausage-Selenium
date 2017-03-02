<?php

require_once 'bootstrap.php';

use MP\Fixtures\Admin\AdminLogin;
use MP\Fixtures\App\Service;

class MagentoAdminOrders extends MP\Sauce\WebDriverTestCase
{
    use AdminLogin, Service;

    /**
     * Admin login before start tests
     */
    public function setUp()
    {
        parent::setUp();

        $this->prepareSession();
        $this->adminLogin();
    }

    /**
     * Logout after test
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->adminLogout();
    }

    /**
     * Testing order creating with existing customer
     *
     * 1. Move to create order page
     * 2. Select first customer
     * 3. Select a store
     * 4. Add product to order
     * 5. Add new shipping and billing address
     * 6. Set shipping method
     * 7. Set payment method
     * 8. Save order
     *
     * TODO: Use test customer from config
     */
    public function testOrderCreateExistingCustomer()
    {
        if (!$this->isLogin) return;

        $this->moveToCreateOrderPage();

        // Select first customer
        $this->byXPath('//*[@id="sales_order_create_customer_grid_table"]/tbody/tr[1]')
            ->click();

        sleep(2);

        // Select a store
        $this->selectStore();

        // Add product
        $this->addProduct(1);

        // Add new address
        $this->setCustomerAddress('_existing', true);

        // Set shipping before payment, or shipping will reset payment info
        $this->setShippingInfo();
        $this->setPaymentInfo();

        // Save order
        $this->saveOrder();
    }

    /**
     * Testing order creating with new customer
     *
     * 1. Move to create order page
     * 2. Select creating new customer
     * 3. Select a store
     * 4. Add product to order
     * 5. Add new shipping and billing address
     * 6. Set email
     * 7. Set payment method
     * 8. Set shipping method
     * 9. Save order
     */
    public function testOrderCreateNewCustomer()
    {
        if (!$this->isLogin) return;

        $addCustomer = $this->moveToCreateOrderPage();
        $addCustomer->click();

        sleep(2);

        // Select a store
        $this->selectStore();

        // Add product
        $this->addProduct(2);

        // Set customer info
        $this->setCustomerAddress();
        $this->byId('email')->value(uniqid('test', true) . '@example.com');

        // Payment block should lose focus before set shipping info
        $this->setPaymentInfo();
        $this->setShippingInfo();

        // Save order
        $this->saveOrder();
    }

    /**
     * Testing order editing
     *
     * 1. Move to order list
     * 2. Get first order number
     * 3. Move to order view page
     * 4. Check correct page
     * 5. Move to order edit page
     * 6. Check correct page
     * 7. Add product to order
     * 8. Add address, payment, shipping info
     * 9. Save order
     */
    public function testEditOrder()
    {
        if (!$this->isLogin) return;

        $this->url($this->adminUrl . '/sales_order');

        //Get order number
        $orderNumber = trim($this->byXPath('//*[@id="sales_order_grid_table"]/tbody/tr[1]/td[2]')->text());

        // Move to order view page
        $this->byXPath('//*[@id="sales_order_grid_table"]/tbody/tr[1]')
            ->click();

        sleep(2);

        @$this->assertContains(
            "Order # {$orderNumber}",
            $this->byXPath('//*[@id="content"]/div/div[2]/h3')->text()
        );

        // Move to edit order page
        $this->byXPath('//*[@id="content"]/div/div[2]/p/button[2]')->click();
        $this->acceptAlert();

        sleep(2);

        // Check page load
        $this->assertContains(
            "Edit Order #{$orderNumber}",
            $this->byXPath('//*[@id="order-header"]/h3')->text()
        );

        // Add product
        $this->addProduct(3);

        // Add new address
        $this->setCustomerAddress('_edit', true);

        // Set shipping before payment, or shipping will reset payment info
        $this->setShippingInfo();
        $this->setPaymentInfo();

        // Save order
        $this->saveOrder();
    }

    /**
     * Move to create order start page
     *
     * 1. Move to orders page
     * 2. Click create new order button
     * 3. Check create customer button
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element
     */
    public function moveToCreateOrderPage()
    {
        $this->url($this->adminUrl . '/sales_order');

        $this->byXPath('//*[@id="page:main-container"]/div[2]/table/tbody/tr/td[2]/button')
            ->click();

        $addCustomer = $this->byXPath('//*[@id="order-customer-selector"]/div/div[1]/div/button');

        $this->assertTrue(!empty($addCustomer->text()));

        return $addCustomer;
    }

    /**
     * Select store for order
     *
     * 1. Check title form store block
     * 2. Click on default store
     * 3. Check crate page title
     */
    public function selectStore()
    {
        $title = $this->byXPath('//*[@id="order-store-selector"]/div/div[1]/h4');
        $this->assertContains("Please Select a Store", $title->text());

        $this->byXPath("//*[@id='order-store-selector']/div/div[2]/div/div/span/label[contains(.,'Default Store View')]")
            ->click();

        sleep(4); // waiting page load

        $title = $this->byXPath('//*[@id="order-items"]/div/div[1]/h4');
        $this->assertContains("Items Ordered", $title->text());
    }

    /**
     * Set customer billing address
     *
     * @param string $prefix
     * @param bool $addNew
     */
    public function setCustomerAddress($prefix = '', $addNew = false)
    {
        // Add new address, same for shipping and billing
        if ($addNew) {
            $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);
            $this->select($this->byId('order-billing_address_customer_address_id'))
                ->selectOptionByLabel('Add New Address');

            $script = "return function() {
                var el = document.getElementById('order-shipping_as_billing');

                if (!el.checked) {
                    el.click();
                }

                return true;
            }();";

            $this->execute(
                array(
                    'script' => $script,
                    'args' => array()
                )
            );

            sleep(2);
        }

        $firstName = $this->byId('order-billing_address_firstname');
        $firstName->clear();
        $firstName->value('NameTest' . $prefix);

        $lastName = $this->byId('order-billing_address_lastname');
        $lastName->clear();
        $lastName->value('LastNameTest' . $prefix);

        $street = $this->byId('order-billing_address_street0');
        $street->clear();
        $street->value('123 Main str.');

        $city = $this->byId('order-billing_address_city');
        $city->clear();
        $city->value(!$prefix ? 'Montgomery' : 'Juneau');

        $this->select($this->byId('order-billing_address_region_id'))
            ->selectOptionByValue(!$prefix ? 1 : 2);

        $postcode = $this->byId('order-billing_address_postcode');
        $postcode->clear();
        $postcode->value('93001');

        $telephone = $this->byId('order-billing_address_telephone');
        $telephone->clear();
        $telephone->value('89999999999');
    }

    /**
     * Get product by specific number and add it to order
     *
     * @param int $number
     */
    public function addProduct($number = 1)
    {
        $this->byCssSelector('#order-items .form-buttons button.add')->click();
        $this->byXPath("//*[@id='sales_order_create_search_grid_table']/tbody/tr[{$number}]")
            ->click();

        sleep(2);

        // Submit form
        $this->byCssSelector('#order-search .entry-edit-head button.add')
            ->click();

        sleep(2);

        $total = $this->byXPath('//*[@id="order-items_grid"]/table/tfoot/tr/td[1]');
        $this->assertTrue(!empty($total->text()));
    }

    /**
     * Set info for new card or select saved
     * Payment block should lose focus before set shipping info
     */
    public function setPaymentInfo()
    {

        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        // Check if customer has saved cards 
        $script = "return function() {
	        var cards = document.querySelectorAll('#saved-cards li input');
        
            return cards.length > 0 ? true : false;
        }();";

        $savedCards = $this->execute(
            array(
                'script' => $script,
                'args' => array()
            )
        );
        
        // Select saved card
        if ($savedCards) {
            $this->byXPath('//*[@id="saved-cards"]/li[1]/input')->click();

            $this->waitForHidden(
                '#loading-mask',
                10
            );

            return;
        }

        $this->byId('cryozonic_stripe_cc_owner')->value('Test Owner');

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->byId('cryozonic_stripe_cc_number')->value('4242424242424242');

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->select($this->byId('cryozonic_stripe_expiration'))
            ->selectOptionByValue(1);

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->select($this->byId('cryozonic_stripe_expiration_yr'))
            ->selectOptionByValue(2020);

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->byId('cryozonic_stripe_cc_cid')->value('555');

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEUP);

        sleep(10);
        
        $this->byXPath('//*[@id="order-shipping-method-summary"]/a')->click();

        $this->waitForHidden(
            '#loading-mask',
            10
        );

        return;
    }

    /**
     * Set shipping info
     */
    public function setShippingInfo()
    {
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEUP);

        $this->waitForHidden(
            '#loading-mask',
            10
        );
        
        $this->byXPath('//*[@id="order-shipping-method-summary"]/a')->click();

        $this->waitForHidden(
            '#loading-mask',
            10
        );

        $this->byXPath('//*[@id="order-shipping-method-choose"]/dl/dd[1]/ul/li/input')
            ->click();

        $this->waitForHidden(
            '#loading-mask',
            10
        );
    }

    /**
     * Save order and check success message
     */
    public function saveOrder()
    {
        $this->waitForHidden(
            '#loading-mask',
            10
        );

        $this->byXPath('//*[@id="order-totals"]/div/div[2]/p[3]/button')->click();

        $this->waitForDisplayed(
            '#messages ul',
            10
        );

        $message = $this->byXPath('//*[@id="messages"]/ul/li/ul/li/span');
        $this->assertContains('The order has been created', $message->text());
    }
}
