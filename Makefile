run_all_in_parallel:
  make -j2 magento_cart magento_registration magento_page_loading magento_admin_orders m1_checkout

magento_cart:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/magento_cart.php

magento_registration:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/magento_registration.php

magento_page_loading:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/Magento_Page_Loading.php

magento_admin_orders:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/Magento_Admin_Orders.php

m1_checkout:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/M1_Checkout.php
