<?php

class ExchangeRateField extends DropdownField {

	function FieldHolder($properties = array()) {

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe-currency/javascript/ExchangeRateField.js');

		return parent::FieldHolder();
	}

	public function Type() {
		return 'exchangerate';
	}
}

