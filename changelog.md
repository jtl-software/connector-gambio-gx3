2.23.0
------
- CO-2096 - support brand field

2.22.1
------
- CO-1965 - fix tax calc if vat id is present
- CO-2034 - fix missing payment info for invoice pull

2.22.0
------
- CO-1867 - Fixed compatibility issues on import with Gambio 4.5
- Revised variations update logic 

2.21.0
------
- CO-1914 - Fixed setting stock quantity for new products
- CO-1916 - Fixed importing surcharge in customer order for minimum quantity 
- CO-1917 - Fixed problem with importing product without manufacturer
- Improved GxApplication initialization process

2.20.0
------
- CO-1714 - Fixed PayPal Transaction ID
- CO-1845 - Fixed special price to early deactivated
- CO-1860 - Fixed delivery note is created several times
- CO-1882 - Fixed duplicate product options in the frontend

2.19.0
------
- CO-999 - Added support for additional presentation forms of the variations
- Fixed missing orders_total_id

2.18.0
------
- CO-1308 - Added integration with library 'jtl/connector-components-xtc'
- CO-1508 - Added integration with Gambio Services, status change 

2.17.0
------
- CO-1462 - Added product tax class guessing on product push
- CO-1015 - Added extended error info messages

2.16.3
------
- Hotfix manufacturer, category image push

2.16.2
------
- Hotfix variation images

2.16.1
------
- Fixed product image push
- Added foreign key check on image push/delete

2.16.0
------
- CO-1521 - Fixed image deletion when product is removed
- CO-1520 - Fixed image is uploaded without extension 
- CO-1501 - Feature do not overwrite features.json on connector update
- CO-1361 - Fixed saving tracking url
- CO-905 - Fixed product new release date 
- CO-490 - Fixed invalid property for customer note

2.15.1
------
- CO-1385 - Added variation values to names of product variation children during import
- CO-1387 - Fixed old variations are were not getting removed after renaming
- CO-1412 - Added support for older versions of gambioultra module

2.15.0
------
- CO-1389 - Gambio 4.3 compatibility
- CO-1369 - Fixed invalid country code in customer order
- CO-1358 - Added gm_show_date_added to special attributes

2.14.0
------
- Fixed problems with payment import

2.13.1
------
- Fixed problem with wrong default value type for product shipping time

2.13.0
------
- Customer groups pull fixed, admin group is now imported
- CO-1156 - Fixed payment import of PayPalPlus
- CO-1065 - Fixed unit name in base price

2.12.1
------
- Fixed type error when parent id is null in category index helper

2.12.0
------
- CO-1080 - Product category cache rebuilding 
- CO-1127 - Fixed problems with DateTime property import

2.11.1
-----
- Fixed problem with product price quicksync in conjunction with variation (combi) children
- Added php cs fixer
- CO-1040 Fixed error messages for different push calls

2.11.0-beta
-----
- CO-1038 Added compatibility to Gambio 4.1
- Fixed category attribute translations

2.10.7
-----
- CO-963 Fixed bug when adding new measurement units

2.10.6
-----
- CO-963 Measurement unit id bug on global data push
- CO-894 Coupon order position imported with wrong vat from time to time
- CO-869 Price calculation in order item is wrong when product is vat exempt

2.10.5
-----
- CO-775 The connector can now differ between guests and registered customers
- CO-626 The manufacturer meta-data will now be correctly pulled
- CO-814 The connector will now correctly pull EANs form simple variations
- CO-759 Deactivated products won't have the isActive flag set to false
- CO-615 The integration tests are fully implemented an the following bugs were resolved
- The pulled category and product status will no be set in relation to the shop settings
- The product keywords will now be correctly pulled
- The special prices active_from_date will now be handled

2.10.3
-----
- CO-698 Fixed Category push for 3.11.1

2.10.2
-----
- CO-498 Added transactions in handle method 
- CO-244/CO-508 Fixed tax rates for CustomerOrderItem's (Now ignoring allow_tax)
- CO-590 Entries in table orders_total with class ot_subtotal_no_tax will be ignored for tax calculation
- CO-601 Existing additional fields will be marked as multilingual during product push
- Comment will be initialised when inserting tracking code into shop database during delivery note push

2.10.1
-----
- Deleting of products_properties_admin_select entries moved to the right place

2.10.0
-----
- CO-387 Enable/Disable developer logging via install gui
- CO-414 Do not send custom fields config flag added
- CO-503 Remove selling unit from a product varcombi parent correctly
- CO-509 Create thumbnails without white borders and in the correct size
- CO-519 "Neu im Sortiment" functionality support
- CO-523 Create and delete products_properties_admin_select table entries properly
- CO-524 Set product keywords via products_keywords attribute
- CO-525 Clear product and category cache properly
- CO-529 Configuration flag "use_combi_child_shipping_time" added

2.9.7
-----
- CO-454 The customer, oder_shipping_address and order_delivery_address will now consider a separate house if activated
- CO-464 Added new category and product attributes
- Hotfix: The connector now won't stop if the shop url contains "install"
- CO-438 The categories bottom description will be filled
- CO-440 The Categories bottom description won't throw errors in older versions
- Updated the build routine

2.9.6
-----
- CO-393 Manufacturer push logic revised
- CO-405 Do not try to map unsupported types
- Set default values for categories_description columns
- Autoloading changed to psr-4
- Update logic revised

2.9.5
-----
- CO-287 Permit negative stock will be considered for VarKombi children during product pull
- CO-282 ProductSpecialPrice handling fixed in ProductSpecialPrice push
- CO-309 WAWIs "Adresszusatz" in Customer and Order Address will be mapped to Gambios "*_additional_info" instead of "*_suburb"
- CO-321 coupons and discounts will be imported with the correct type during CustomerOrder pull
- (Measurement-)Unit handling of products completely revised
- Handling of product variation prices revised in product push

2.9.4
-----
- CO-264 Images will be still linked to the related product variations after product push
- CO-259 endpoint_id column in jtl_connector_link_product table extended to 255
- Product attributes will be imported multilingual in Wawi during product pull
- Manufacturer push refactored

2.9.3
-----
- Product variation attributes will be ignored during product push

2.9.2
-----
- Special attributes will not created as additional field during product push

2.9.1
------
 - CO-244 Consider allow_tax flag on order_items when pulling a CustomerOrder
 - Implement GambioHub-Payments support
 
2.2
------

- [b21d021]
  CO-31 #resolve removed ProductVarCombination

- [c1e3a62]
  push varcombi prices as difference to master product price

- [427d706]
  added tmp fallback

- [1544cd2]
  bugfixes and improvements

- [38d7f33]
  ignore invalid languages

- [993c6c1]
  bugfix for varcombi links

- [488941b]
  bugfixes

- [27ec22d]
  bugfix for non-default db settings

- [5c55d95]
  fixes for gambio db changes

- [338d82c]
  fixed key mapper

- [b8a1e54]
  bugfixes for payment methods and measurement unit pull

- [23b22e9]
  refactored mapping to multiple tables
  added update migrations

- [1c7cf5a]
  fixed variation name

- [9122c68]
  avoid deletion of special prices

1.10
------
- [f8f81cb]
  updated changelog

- [db9abdd]
  changed category i18n push to delta update instead delete/insert
  append variation values for order item names

- [1115d0d]
  updated changelog

1.9
------
- [76733de]
  added image i18ns pull
  added additional category attr
  fixed order tax calculations
  fixed order variations

- [5fbf854]
  fixed coupon price

- [5326be5]
  workaround to update i18ns instead of delete/insert

1.7
------
- [d99d025]
  raised version

- [b688c31]
  added varcombi images
  fixed payment status mapping
  refactored plugin autoloader

- [9c4f2c5]
  workaround for shipping name

1.6
------
- [9a546a9]
  improved product pull queries
  added delivery tax rate
  added order discounts
  removed pending status

- [acde288]
  added delivery note push (tracking codes)

1.5
------
- [d78603f]
  fixed mapping table creation
  fixed order item price
  fixed endpoint version identify

- [bb9c0d1]
  add link table index checks

- [ff64bb2]
  image bugfix

- [dce4316]
  fixed image pull sort

1.4
------
- [ce87fe5]
  added product templates

- [e95e3ae]
  fixed utf8 encoding

- [1829dde]
  fixed additional costs/discounts for orders

- [335b99e]
  fixed iso mappings

- [bd35f1a]
  added crossselling id

- [7e3ed3c]
  added utf8 encode option for hacked shops/themes

1.3
------
- [324044a]
  fixed encoding
  fixed some invalid queries
  improved variation performance

- [7d05da5]
  updated changelog

1.2
------
- [17de93b]
  fixed deletion of custom fields
  removed old config

1.1
------
- [3164d7b]
  fixed variation delete
  raised connector version
  added changelog

1.0.10
------
- [03b193d]
  seo url rewrite for invalid urlpath

