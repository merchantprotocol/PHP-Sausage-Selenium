<?php

require_once 'bootstrap.php';

use MP\Fixtures\Admin\AdminLogin;

class MagentoPageLoading extends MP\Sauce\WebDriverTestCase
{
    use AdminLogin;

    const PAGE_TYPE_ADMIN       = 'admin';
    const PAGE_TYPE_ORDER       = 'order';
    const PAGE_TYPE_FRONT       = 'front';
    const PAGE_TYPE_CUSTOMER    = 'customer';

    private static $frontFailList       = [];
    private static $customerFailList    = [];
    private static $adminFailList       = [];
    private static $orderFailList       = [];

    /**
     * Testing of pages loading in admin area
     *
     * 1. Login into admin area
     * 2. Check pages with custom layout
     * 3. Get all links from admin area
     * 4. Move to each link
     * 5. Check Magento version in footer
     */
    public function testAdminPagesLoading()
    {
        $this->adminLogin();
        $this->adminPageLoading($this->url(), self::PAGE_TYPE_ADMIN);

        $this->customAdminPagesLoading();

        $links = $this->getAllAdminLinks();

        if (!is_array($links) || empty($links)) {
            $this->fail('Admin links not found');
        }

        foreach($links as $link) {
            $isCustom = $this->checkCustomPageLayout($link);

            if ($isCustom === true) {
                continue;
            }

            $this->url($link);

            $this->adminPageLoading($link, self::PAGE_TYPE_ADMIN);
        }

        if (!empty(self::$adminFailList)) {
            $this->fail(
                sprintf(
                    "Failed admin pages - %d\n%s",
                    count(self::$adminFailList),
                    print_r(self::$adminFailList, true)
                )
            );
        }
    }

    /**
     * Testing of recurring orders loading
     *
     * 1. Move to orders list with specific limit
     * 2. Get all links from orders grid
     * 3. Move to each link
     * 4. Check Magento version in footer
     */
    public function testRecurringOrdersLoading() {
        $this->adminLogin();
        $this->adminPageLoading($this->url(), self::PAGE_TYPE_ADMIN);

        $ordersCount = $this->getTestConfig()->getValue('orders_count');
        $ordersUrl =  $this->adminUrl . '/adminhtml_recurring/index/limit/' . $ordersCount . '/';

        $this->url($ordersUrl);
        $this->adminPageLoading($ordersUrl, self::PAGE_TYPE_ORDER);

        $links = $this->getAllOrdersLinks();

        if (!is_array($links) || empty($links)) {
            $this->fail('Orders links not found');
        }

        foreach($links as $link) {
            $this->url($link);

            $this->adminPageLoading($link, self::PAGE_TYPE_ORDER);
        }

        if (!empty(self::$orderFailList)) {
            $this->fail(
                sprintf(
                    "Failed order pages - %d\n%s",
                    count(self::$orderFailList),
                    print_r(self::$orderFailList, true)
                )
            );
        }
    }

    /**
     * Testing of pages loading in frontend
     *
     * 1. Move to home page
     * 2. Check Magento version in footer
     * 3. Get all links from admin area
     * 4. Move to each link
     * 5. Check Magento version in footer
     */
    public function testFrontPagesLoading()
    {
        $this->url('/');

        $this->frontendPageLoading($this->url(), self::PAGE_TYPE_FRONT);

        $links = $this->getAllFrontendLinks();

        if (!is_array($links) || empty($links)) {
            $this->fail('Frontend links not found');
        }

        foreach($links as $link) {
            $this->url($link);

            $this->frontendPageLoading($link, self::PAGE_TYPE_FRONT);
        }

        if (!empty(self::$frontFailList)) {
            $this->fail(
                sprintf(
                    "Failed frontend pages - %d\n%s",
                    count(self::$frontFailList),
                    print_r(self::$frontFailList, true)
                )
            );
        }
    }

    /**
     * Testing of pages loading in customer account
     *
     * 1. Create new customer account
     * 2. Move to account page
     * 3. Get all links from customer account
     * 4. Move to each link
     * 5. Check Magento version in footer
     */
    public function testCustomerPagesLoading()
    {
        $this->create();

        $this->url('/customer/account');

        $this->frontendPageLoading($this->url(), self::PAGE_TYPE_CUSTOMER);

        $links = $this->getAllCustomerLinks();

        if (!is_array($links) || empty($links)) {
            $this->fail('Customer links not found');
        }

        foreach($links as $link) {
            $this->url($link);

            $this->frontendPageLoading($link, self::PAGE_TYPE_CUSTOMER);
        }

        if (!empty(self::$customerFailList)) {
            $this->fail(
                sprintf(
                    "Failed customer pages - %d\n%s",
                    count(self::$customerFailList),
                    print_r(self::$customerFailList, true)
                )
            );
        }
    }

    /**
     * Check custom admin pages
     */
    public function customAdminPagesLoading() {
        $dashboardLink = $this->adminUrl . '/enhanced/dashboard';

        $this->url($dashboardLink);

        try {
            $el = $this->byXPath("//*[@id=\"page-wrapper\"]/div[1]/div/div/h2");

            $this->assertContains('MP Enhanced Dashboard', $el->text());
        } catch (Exception $e) {
            self::$adminFailList[] = $dashboardLink;
        }
    }

    /**
     * Creates a new customer account
     *
     * TODO: use test customer from config instead
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
        $this->byId('email_address')->value(uniqid('test', true) . '@example.com');
        $this->byId('password')->value('1q2w3e4r');
        $this->byId('confirmation')->value('1q2w3e4r');

        $this->byXPath('//*[@id="form-validate"]/*/button[contains(@class,"button")]')->click();
        sleep(2);
        $this->assertContains("Account", $this->title());
    }

    /**
     * Check admin page load
     *
     * @param $link
     * @param $pageType
     */
    public function adminPageLoading($link, $pageType)
    {
        if (!is_string($link)) {
            $this->fail('Link is not a string');
        }

        try {
            $el = $this->byXPath("//*[@id=\"html-body\"]/div[1]/div[3]");

            $this->assertContains('Magento ver.', $el->text());
        } catch (Exception $e) {
            if ($pageType === self::PAGE_TYPE_ADMIN) {
                self::$adminFailList[] = $link;
            } else if ($pageType === self::PAGE_TYPE_ORDER) {
                self::$orderFailList[] = $link;
            }
        }
    }

    /**
     * Check frontend page load
     *
     * @param $link
     * @param $pageType
     */
    public function frontendPageLoading($link, $pageType)
    {
        if (!is_string($link)) {
            $this->fail('Link is not a string');
        }

        try {
            // Selenium WebDriver will only interact with visible elements
            $script = "return function() {
                var version = document.querySelectorAll('#current_version strong');
                var contents = '';

                return version.length > 0 ? version[0].innerHTML : contents;
            }();";

            $version = $this->execute(
                array(
                    'script' => $script,
                    'args' => array()
                )
            );

            $this->assertTrue((is_string($version) && !empty($version)));
        } catch (Exception $e) {
            if ($pageType === self::PAGE_TYPE_FRONT) {
                self::$frontFailList[] = $link;
            } else if ($pageType === self::PAGE_TYPE_CUSTOMER) {
                self::$customerFailList[] = $link;
            }
        }
    }

    /**
     * Returns array with all links from admin nav menu
     *
     * Getting all links via js script is faster way,
     * than calling $link->attribute(); method for each links
     *
     * @return array
     */
    public function getAllAdminLinks()
    {
        $this->url($this->adminUrl);

        $script = "return function() {
	        var links = document.querySelectorAll('#nav a:not([href^=\"#\"])');
            var contents = [];
            for (i = 0; i < links.length; i++) {
                contents.push(links[i].href);
            }
            return contents;
        }();";

        return $this->execute(
            array(
                'script' => $script,
                'args' => array()
            )
        );
    }

    /**
     * Returns array with all links from recurring orders grid,
     * it is more universal way, than the calculation from the last order ID
     *
     * Getting all links via js script is faster way,
     * than calling $link->attribute(); method for each links
     *
     * @return array
     */
    public function getAllOrdersLinks()
    {
        $script = "return function() {
	        var orders = document.querySelectorAll('#recurring_grid_table tbody tr');
            var contents = [];
            for (i = 0; i < orders.length; i++) {
                contents.push(orders[i].title);
            }
            return contents;
        }();";

        return $this->execute(
            array(
                'script' => $script,
                'args' => array()
            )
        );
    }

    /**
     * Returns array with all links from frontend
     *
     * @return array
     */
    public function getAllFrontendLinks()
    {
        return $this->getTestConfig()->getValue('frontend_links');
    }

    /**
     * Returns array with all links from customer account
     *
     * @return array
     */
    public function getAllCustomerLinks()
    {
        $script = "return function() {
	        var links = document.querySelectorAll('.main .col-left .block-account ul li a');
            var contents = [];
            for (i = 0; i < links.length; i++) {
                contents.push(links[i].href);
            }
            return contents;
        }();";

        return $this->execute(
            array(
                'script' => $script,
                'args' => array()
            )
        );
    }

    /**
     * Returns true if page has custom layout
     *
     * @param string $link
     * @return bool
     */
    public function checkCustomPageLayout($link)
    {
        $isCustom = false;

        if (!is_string($link)) {
            return $isCustom;
        }

        $customLayoutPages = $this->getTestConfig()->getValue('admin_custom_links');

        if (empty($customLayoutPages)) {
            return $isCustom;
        }

        foreach($customLayoutPages as $page) {
            if (strpos($link, $this->adminUrl . $page) !== false) {
                $isCustom = true;
                break;
            }
        }

        return $isCustom;
    }
}