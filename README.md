SwipeStripe Currency
====================

Maintainer Contact
------------------
SwipeStripe  
[Contact Us](http://swipestripe.com/support/contact-us)

Requirements
------------
* SilverStripe 3.*
* Swipe Stripe 2.*

Documentation
-------------
Multiple currency support. Base currency is used to process orders with the payment gateway.

Installation Instructions
-------------------------
1. Place this directory in the root of your SilverStripe installation, rename the folder 'swipestripe-currency'.
2. Visit yoursite.com/dev/build?flush=1 to rebuild the database.

Usage Overview
--------------
1. Create exchange rates in the Shop settings area, make sure there is an exchange rate for the base currency with a 1:1 ratio.
2. Add $CurrencyForm to your template
