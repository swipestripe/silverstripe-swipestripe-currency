<?php
/**
 * Default settings.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage admin
 */

//Extensions
Object::add_extension('ShopConfig', 'ExchangeRate_ShopConfigExtension');
Object::add_extension('Page_Controller', 'ExchangeRate_PageControllerExtension');

Object::add_extension('Product', 'ExchangeRate_Extension');
Object::add_extension('Variation', 'ExchangeRate_Extension');

Object::add_extension('Order', 'ExchangeRate_OrderExtension');
Object::add_extension('Item', 'ExchangeRate_OrderRelatedExtension');
Object::add_extension('ItemOption', 'ExchangeRate_OrderRelatedExtension');
Object::add_extension('Modification', 'ExchangeRate_OrderRelatedExtension');