2.9.6
-----
- CO-393 Manufacturer push logic revised
- CO-405 Do not try to map unsupported types
- Autoloading changed to psr-4
- Update logic revised

2.9.5-RC
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

