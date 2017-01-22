<?php

require_once 'vendor/autoload.php';

class MagentoCustomerAccount extends Sauce\Sausage\WebDriverTestCase
{
    protected $base_url = 'http://development-secure-1758740052.us-west-2.elb.amazonaws.com/';

    public static $browsers = array(
        array(
            'browserName' => 'firefox',
            'desiredCapabilities' => array(
                'version' => '35',
                'platform' => 'Windows 7'
            )
        )
    );

    protected $tmpEmail;

    /**
     * Create random customer
     */
    public function testCreateAccount()
    {
        $this->url('customer/account/login/');
        $this->assertContains('Customer Login', $this->title());

        $this->byXPath('//*[contains(@class,"new-users")]/*/a[contains(@class,"button")]')->click();
        sleep(2);

        // Validate Create Account Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('customer-account-create', $body->attribute('class'));

        $this->byId('firstname')->value('John');
        $this->byId('lastname')->value('Doe');
        $this->byId('email_address')->value($this->getTmpEmail());
        $this->byId('password')->value('1q2w3e4r');
        $this->byId('confirmation')->value('1q2w3e4r');

        $this->byXPath('//*[@id="form-validate"]/*/button[contains(@class,"button")]')->click();
        sleep(2);

        // Validate Account Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('customer-account-index', $body->attribute('class'));
    }

    public function testAccountInformation()
    {
        $this->byXPath('/html/body/div[1]/div/div[1]/div/div[1]/div/div[2]/ul/li[2]')->click();
        sleep(2);

        // Validate Customer Account Edit Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('customer-account-edit', $body->attribute('class'));

        $this->byId('firstname')->value('John Edit');
        $this->byId('lastname')->value('Doe Edit');
        $this->byId('email_address')->value('edited_' . $this->getTmpEmail());

        $this->byXPath('//*[@id="form-validate"]/*/button[contains(@class,"button")]')->click();
        sleep(2);

        // Validate Success
        $body = $this->byXPath('//html/body');
        $this->assertContains('customer-account-index', $body->attribute('class'));

        $dashboard = $this->byXPath('/html/body/div[1]/div/div[1]/div/div[2]/div/div');
        $this->assertTextPresent('The account information has been saved.', $dashboard);
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
