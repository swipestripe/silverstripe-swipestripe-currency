<?php

class ExchangeRateField extends DropdownField {

	function FieldHolder() {

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript('swipestripe-currency/javascript/ExchangeRateField.js');

		return parent::FieldHolder();
	}

	public function Type() {
		return 'exchangerate';
	}
}

