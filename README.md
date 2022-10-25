# Viabill payments for Odoo

Odoo is an ERP system which is written in Python. Viabill has an PHP library integrated with Odoo right now. Merchants can install Viabill manually like below:

- Integrating Viabill Odoo Module to Odoo (We suppose that you are using your own server-side Odoo, not cloud based Odoo.)
- Running PHP Library in the same or different server

## 1. Integrating Viabill Odoo Module to Odoo

### 1.1 Adding Scripts to Odoo
1) Go into odoo server and in the main directory paste extra-addons in here. If you have a folder for  similar purposes, just paste payment_viabill to this folder.  
2) Go to the main odoo.conf file, and add options like in config/odoo.conf. If you don’t know where the main odoo.conf file is, there is a good chance it is in the /etc/odoo directory.
3) In payment_viabill/views pricetag script in product_pricetag_views.xml and cart_pricetag_views.xml with you script. You don’t have to touch the div element.
4) Inside the Odoo web interface admin panel go to Apps and click “Update Apps List”.
![](/images/odoo_step_4.jpg)
5) You will see Viabill Payments Acquirer in your apps.
![](/images/odoo_step_5.jpg)
6) Click Install. And the ViaBill App should be installed.
7) Go to Invoicing -> Configuration -> Payment Acquirers and you will see ViaBill as Payment Acquirer.
![](/images/odoo_step_7.jpg)
8) Click Activate.
![](/images/odoo_step_8.jpg)
9) Choose Test Mode, or Enabled for making it visible in the payment page.
10) Enter your PHP Library’s domain in Viabill Base Url in the Credentials tab.
11) In the Configuration tab if you want to auto-capture transactions uncheck “Capture Amount Manually” so ViaBill will capture the payments automatically. Choose Payment Journal  as well.  And click Save.
![](/images/odoo_step_11.jpg)

### 1.2 Integrated Odoo
#### 1.2.2 Pricetags
Pricetags are being shown in product and cart page.

<p align="center">
![](/images/pricetags_1.jpg)
</p>

<p align="center">
![](/images/pricetags_2.jpg)
</p>

#### 1.2.2 Payment Page
In the payment page you should see ViaBill Payments as well. According to your decision it will be in Live or Test Mode. 

<p align="center">
![](/images/payments_1.jpg)
</p>

#### 1.2.3 Transaction Reports
In your admin panel if you go to Invoicing->Configuration->Payment Transactions you will see the transaction reports that were made. If you want, you can capture or cancel the transactions.

<p align="center">
![](/images/transactions_1.jpg)
</p>

## 2. PHP Library Activation
In the PHP Library first you must configure the .env file.

| Parameter | Value |
| ------ | ------ |
| VIABILL_APP_KEY | Merchant's app key. It is provided by Viabill. |
| VIABILL_SECRET_KEY | Merchant's secret key. It is provided by Viabill.  |
| ODOO_BASE_URL | Your Odoo website’s domain name. It is used by the PHP library to make requests to the Odoo site. |
| IP_RESTRICTION | A single or multiple IP addresses (comma separated) that are allowed to access the PHP library. It should be set to the IP address of the Odoo site that will make the requests to the Viabill's PHP library. |

### 2.1 Docker Users
After configuring .env file run:
```sh
docker-compose build
docker-compose up
```
Bind ssl and a domain to this PHP Library

### 2.2 Non-Docker Users 
You must run the library in php 8.
Bind ssl and a domain to this PHP Library.
