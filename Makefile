run_all_in_parallel:
	make -j magento_cart magento_customer_account magento_registration

magento_cart:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/magento_cart.php

magento_customer_account:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/magento_customer_account.php

magento_registration:
	vendor/bin/paratest -p 4 -f --phpunit=vendor/bin/phpunit tests/magento_registration.php
