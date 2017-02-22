<?php

require_once 'vendor/autoload.php';

class MagentoCart extends Sauce\Sausage\WebDriverTestCase
{
    protected $base_url = 'http://127.0.0.1/';

    public static $browsers = array(
        array(
            'browserName' => 'firefox',
            'desiredCapabilities' => array(
                'version' => '35',
                'platform' => 'Windows 7'
            )
        )
    );

    /**
     * Testing of add product to cart procedure
     *
     * 1. Select and click sale button (Home Page)
     * 2. Check if it's the product page
     * 3. Select and click sale button
     * 4. Check if it's the checkout page
     */
    public function testAddProductToCart()
    {
        $this->url('/');
        sleep(2);

        $body = $this->byXPath('//*[@id="current_version"]/strong');
        $this->assertNotNull($body->text());
        
        // Validate Home Page
        //$body = $this->byXPath('//html/body');
        //$this->assertContains('cms-index-index', $body->attribute('class'));
        
        // Purchase by homepage
        $this->byCssSelector('#home-intro .btn-buy')->click();

        // Validate Product Page
        $body = $this->byXPath('//html/body');
        $this->assertContains('catalog-product-view', $body->attribute('class'));

        // Purchase by product page
        $this->byCssSelector('#product-efile-box .btn-buy')->click();
        sleep(2);
        
        $this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::PAGEDOWN);

        // Validate Checkout Page (Product added successfully)
        $body = $this->byXPath('//html/body');
        $this->assertContains('mp-checkout-index-index', $body->attribute('class'));
    }
}
