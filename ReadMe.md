# GET EXTENSION

Use modman [Linux](https://github.com/colinmollenhour/modman) | [Windows](https://github.com/khoazero123/modman-php) :

	cd magento_root/
    modman clone https://github.com/devfgct/OnePay.git
	modman deploy OnePay

Use git:

    git clone https://github.com/devfgct/OnePay.git
    mv OnePay/* magento_root/


# INSTALLATION

	bin/magento setup:upgrade
	bin/magento setup:di:compile

# APPLY MOD
### Nếu OnePay đã được cài đặt trước đó thì cần chạy các commands dưới đây để chèn email template
	bin/magento setup:upgrade
	bin/magento setup:di:compile
	bin/magento onepay:test importEmailTemplate