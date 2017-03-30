<?php

require_once 'bootstrap.php';

class AccountTestCase extends MP\Sauce\WebDriverTestCase
{
    protected $tmpEmail;
    
    /**
     * testing of Creation and Log-in procedures
     *
     * 1. Create new customer account
     * 2. Logout
     * 3. Login into created customer account
     */
    public function testStandardAccountFunctions()
    {
        $this->create();

        $this->url('customer/account/logout/');
        sleep(5);// waiting for auto-redirection afte log-out

        $this->login();
        
        // Testing of account info editing 
        $this->editAccountInfo();
        // Testing of adding new address
        $this->addAddress();
        // Testing of orders history page
        $this->checkOrdersHistory();
    }

    /**
     * testing of ForgotPassword procedure
     *
     * 1. Create new customer account
     * 2. Log-out
     * 3. Move to customer/account/forgotpassword/
     * 4. submit form
     */
    public function testForgotPassword()
    {
        // to ensure that email exists we need to create new account before
        $this->create();

        $this->url('customer/account/logout/');
        sleep(5);// waiting for auto-redirection afte log-out

        $this->url('customer/account/login/');
        sleep(2);
        $this->assertContains("Customer Login", $this->title());

        $this->byXPath('//*[@id="login-form"]/div/div[2]/div[1]/ul/li[3]/a')->click();
        sleep(2);
        $this->assertContains("Forgot Your Password", $this->title());

        $this->byId('email_address')->value($this->getTmpEmail());
        $this->byXPath('//*[@id="form-validate"]/div[2]/button')->click();

        sleep(2);
        //$this->assertContains("Customer Login", $this->title());
        $alertMessage = $this->byXPath('//html/body/div/div/div[1]/div/div/div/div[2]')->text();
        $this->assertContains($this->getTmpEmail(), $alertMessage);
    }

    /**
     * Testing of account info editing
     *
     * 1. Change customer's info
     * 2. Check saved data
     */
    public function editAccountInfo()
    {
        $this->url('customer/account/edit/');
        sleep(2);
        
        $this->assertContains(
            'Account Information', 
            $this->byXPath('//*[@id="form-validate"]/div[1]/h2')->text()
        );

        $firstName = $this->byId('firstname');
        $firstName->clear();
        $firstName->value('FirstNameTest');

        $lastName = $this->byId('lastname');
        $lastName->clear();
        $lastName->value('LastNameTest');
        
        $this->byId('change_password')->click();
        $this->byId('current_password')->value('1q2w3e4r');
        $this->byId('password')->value('1q2w3e4r5t');
        $this->byId('confirmation')->value('1q2w3e4r5t');
        
        // Submit
        $this->byXPath('//*[@id="form-validate"]/div[3]/button')->click();
        sleep(5);
        
        // Test
        $this->url('customer/account/edit/');
        sleep(2);

        $this->assertContains(
            'FirstNameTest',
            $this->byId('firstname')->value()
        );

        $this->assertContains(
            'LastNameTest',
            $this->byId('lastname')->value()
        );
    }

    /**
     * Testing of adding new address
     * 
     * 1. Add new address
     * 2. Move to edit this address
     * 3. Check saved data
     */
    public function addAddress()
    {
        $this->url('customer/address/new/');
        sleep(2);
        
        $this->byId('telephone')->value('55555555');
        $this->byId('street_1')->value('Houston Street');

        sleep(2);

        // Use keys for select
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::DOWN);
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);

        sleep(2);
        
        // Submit
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);
        
        $this->byXPath('//*[@id="form-validate"]/div[3]/button')->click();
        sleep(5);
        
        // Test
        $this->url('customer/account/');
        
        $this->byXPath('//*[@id="top"]/body/div[1]/div/div[1]/div/div[2]/div/div/div[4]/div[2]/div[1]/div/div[1]/a')
            ->click();

        sleep(5);
        
        $this->assertContains(
            '55555555',
            $this->byId('telephone')->value()
        );

        $this->assertContains(
            'Houston Street',
            $this->byId('street_1')->value()
        );

        $this->assertContains(
            'Fort Worth',
            $this->byId('city')->value()
        );

        $this->assertContains(
            '76102',
            $this->byId('zip')->value()
        );
    }

    /**
     * Testing of orders history page
     */
    public function checkOrdersHistory()
    {
        $this->url('sales/order/history/');

        $this->assertContains(
            'My Orders',
            $this->byXPath('//*[@id="top"]/body/div[1]/div/div[1]/div/div[2]/div/div[1]/h1')->text()
        );

        $this->assertContains(
            'You have placed no orders',
            $this->byXPath('//*[@id="top"]/body/div[1]/div/div[1]/div/div[2]/div/p')->text()
        );
    }
    
    /**
     * Creates a new customer account
     */
    public function create()
    {
        $this->url('customer/account/login/');
        $this->assertContains("Customer Login", $this->title());

        $this->byXPath('//*[contains(@class,"new-users")]/*/a[contains(@class,"button")]')->click();
        sleep(2);
        $this->assertContains("Create New Customer Account", $this->title());

        $this->byId('firstname')->value('John');
        $this->byId('lastname')->value('Doe');
        $this->byId('email_address')->value($this->getTmpEmail());
        $this->byId('password')->value('1q2w3e4r');
        $this->byId('confirmation')->value('1q2w3e4r');

        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);
        $this->byXPath('//*[@id="form-validate"]/*/button[contains(@class,"button")]')->click();
        sleep(2);
        $this->assertContains("Account", $this->title());

    }

    /**
     * Log-in with existed (created) customer account
     */
    public function login()
    {
        $this->url('customer/account/login/');
        $this->assertContains("Customer Login", $this->title());

        $this->byId('email')->value($this->getTmpEmail());
        $this->byId('pass')->value('1q2w3e4r');

        $this->byId('send2')->click();
        sleep(2);
        $this->assertContains("Account", $this->title());
    }

    /**
     * Returns generated temp email address
     *
     * @return string
     */
    public function getTmpEmail()
    {
        if (is_null($this->tmpEmail)) {
            $this->tmpEmail =  uniqid('test', true) . '@example.com';
        }

        return $this->tmpEmail;
    }
}
