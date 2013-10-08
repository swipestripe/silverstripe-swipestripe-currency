<?php
/**
 * Exchange rates that can be set in {@link SiteConfig}. Several flat rates can be set 
 * for any supported shipping country.
 */
class ExchangeRate extends DataObject {
	
	/**
	 * Fields for this tax rate
	 * 
	 * @var Array
	 */
	private static $db = array(
		'Title' => 'Varchar',
		'Currency' => 'Varchar(3)',
		'CurrencySymbol' => 'Varchar(10)',
		'Rate' => 'Decimal(19,4)',
		'BaseCurrency' => 'Varchar(3)',
		'BaseCurrencySymbol' => 'Varchar(10)',
		'SortOrder' => 'Int'
	);
	
	/**
	 * Exchange rates are associated with SiteConfigs.
	 * 
	 * TODO The CTF in SiteConfig does not save the SiteConfig ID correctly so this is moot
	 * 
	 * @var unknown_type
	 */
	private static $has_one = array(
		'ShopConfig' => 'ShopConfig'
	);

	private static $summary_fields = array(
		'Title' => 'Title',
		'CurrencySymbol' => 'Symbol',
		'Currency' => 'Currency',
		'BaseCurrency' => 'Base Currency',
		'Rate' => 'Rate'
	);

	private static $default_sort = 'SortOrder';

	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$shopConfig = ShopConfig::current_shop_config();
		$this->BaseCurrency = $shopConfig->BaseCurrency;
		$this->BaseCurrencySymbol = $shopConfig->BaseCurrencySymbol;
	}
	
	/**
	 * Field for editing a {@link ExchangeRate}.
	 * 
	 * @return FieldSet
	 */
	public function getCMSFields() {

		$shopConfig = ShopConfig::current_shop_config();
		$baseCurrency = $shopConfig->BaseCurrency;

		return new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('ExchangeRate',
					TextField::create('Title'),
					TextField::create('Currency', _t('ExchangeRate.CURRENCY', ' Currency'))
						->setRightTitle('3 letter currency code - <a href="http://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank">available codes</a>'),
					TextField::create('CurrencySymbol', _t('ExchangeRate.SYMBOL', 'Symbol'))
						->setRightTitle('Symbol to use for this currency'),
					NumericField::create('Rate', _t('ExchangeRate.RATE', 'Rate'))
						->setRightTitle("Rate to convert from $baseCurrency")
				)
			)
		);
	}

	public function getCMSValidator() {
		return new RequiredFields(array(
			'Title',
			'Currency',
			'Rate'
		));
	}

	public function validate() {

		$result = new ValidationResult(); 

		if (!$this->Title || !$this->Currency || !$this->Rate) {
			$result->error(
				'Rate is missing a required field',
				'ExchangeRateInvalidError'
			);
		}
		return $result;
	}
	
}

/**
 * So that {@link ExchangeRate}s can be created in {@link SiteConfig}.
 */
class ExchangeRate_ShopConfigExtension extends DataExtension {

	/**
	 * Attach {@link ExchangeRate}s to {@link SiteConfig}.
	 * 
	 * @see DataObjectDecorator::extraStatics()
	 */
	private static $has_many = array(
		'ExchangeRates' => 'ExchangeRate'
	);

}

class ExchangeRate_Admin extends ShopAdmin {

	private static $tree_class = 'ShopConfig';
	
	private static $allowed_actions = array(
		'ExchangeRateSettings',
		'ExchangeRateSettingsForm',
		'saveExchangeRateSettings'
	);

	private static $url_rule = 'ShopConfig/ExchangeRate';
	protected static $url_priority = 150;
	private static $menu_title = 'Shop Exchange Rates';

	private static $url_handlers = array(
		'ShopConfig/ExchangeRate/ExchangeRateSettingsForm' => 'ExchangeRateSettingsForm',
		'ShopConfig/ExchangeRate' => 'ExchangeRateSettings'
	);

	public function init() {
		parent::init();
		if (!in_array(get_class($this), self::$hidden_sections)) {
			$this->modelClass = 'ShopConfig';
		}
	}

	public function Breadcrumbs($unlinked = false) {

		$request = $this->getRequest();
		$items = parent::Breadcrumbs($unlinked);

		if ($items->count() > 1) $items->remove($items->pop());

		$items->push(new ArrayData(array(
			'Title' => 'Exchange Rate Settings',
			'Link' => $this->Link(Controller::join_links($this->sanitiseClassName($this->modelClass), 'ExchangeRate'))
		)));

		return $items;
	}

	public function SettingsForm($request = null) {
		return $this->ExchangeRateSettingsForm();
	}

	public function ExchangeRateSettings($request) {

		if ($request->isAjax()) {
			$controller = $this;
			$responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->ExchangeRateSettingsForm()->forTemplate();
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith('ShopAdminSettings_Content');
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			); 
			return $responseNegotiator->respond($this->getRequest());
		}

		return $this->renderWith('ShopAdminSettings');
	}

	public function ExchangeRateSettingsForm() {

		$shopConfig = ShopConfig::get()->First();

		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}

		$config = GridFieldConfig_HasManyRelationEditor::create();
		$detailForm = $config->getComponentByType('GridFieldDetailForm')->setValidator(
			singleton('ExchangeRate')->getCMSValidator()
		);
		if (class_exists('GridFieldSortableRows')) {
			$config->addComponent(new GridFieldSortableRows('SortOrder'));
		}

		$fields = new FieldList(
			$rootTab = new TabSet('Root',
				$tabMain = new Tab('ExchangeRates',
					GridField::create(
						'ExchangeRates',
						'ExchangeRates',
						$shopConfig->ExchangeRates(),
						$config
					)
				)
			)
		);

		$actions = new FieldList();
		$actions->push(FormAction::create('saveExchangeRateSettings', _t('GridFieldDetailForm.Save', 'Save'))
			->setUseButtonTag(true)
			->addExtraClass('ss-ui-action-constructive')
			->setAttribute('data-icon', 'add'));

		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);

		$form->setTemplate('ShopAdminSettings_EditForm');
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		$form->addExtraClass('cms-content cms-edit-form center ss-tabset');
		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setFormAction(Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'ExchangeRate/ExchangeRateSettingsForm'));

		$form->loadDataFrom($shopConfig);

		return $form;
	}

	public function saveExchangeRateSettings($data, $form) {

		//Hack for LeftAndMain::getRecord()
		self::$tree_class = 'ShopConfig';

		$config = ShopConfig::get()->First();
		$form->saveInto($config);
		$config->write();
		$form->sessionMessage('Saved Exchange Rate Settings', 'good');

		$controller = $this;
		$responseNegotiator = new PjaxResponseNegotiator(
			array(
				'CurrentForm' => function() use(&$controller) {
					//return $controller->renderWith('ShopAdminSettings_Content');
					return $controller->ExchangeRateSettingsForm()->forTemplate();
				},
				'Content' => function() use(&$controller) {
					//return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
				},
				'Breadcrumbs' => function() use (&$controller) {
					return $controller->renderWith('CMSBreadcrumbs');
				},
				'default' => function() use(&$controller) {
					return $controller->renderWith($controller->getViewer('show'));
				}
			),
			$this->response
		); 
		return $responseNegotiator->respond($this->getRequest());
	}

	public function getSnippet() {

		if (!$member = Member::currentUser()) return false;
		if (!Permission::check('CMS_ACCESS_' . get_class($this), 'any', $member)) return false;

		return $this->customise(array(
			'Title' => 'Exchange Rates Management',
			'Help' => 'Create exchange rates',
			'Link' => Controller::join_links($this->Link('ShopConfig'), 'ExchangeRate'),
			'LinkTitle' => 'Edit exchange rates'
		))->renderWith('ShopAdmin_Snippet');
	}

}

class ExchangeRate_PageControllerExtension extends Extension {

	private static $allowed_actions = array(
		'CurrencyForm',
		'setCurrency'
	);

	public function CurrencyForm() {

		//Get the currencies
		$config = ShopConfig::current_shop_config();

		if ($config && $config->exists()) {
			$exchangeRates = $config->ExchangeRates();

			//If a rate does not exist for base currency 
			if (!in_array($config->BaseCurrency, $exchangeRates->column('Currency'))) {
				Session::clear('SWS.Currency');
				return;
			}

			$currencies = array_combine($exchangeRates->column('Currency'), $exchangeRates->column('Title'));

			$currency = Session::get('SWS.Currency');
			if (!$currency) {
				$currency = $config->BaseCurrency;
			}

			$fields = FieldList::create(
				ExchangeRateField::create('Currency', ' ', $currencies, $currency)
			);

			$actions = FieldList::create(
				FormAction::create('setCurrency', _t('GridFieldDetailForm.Save', 'Save'))
			);

			return new Form(
				$this->owner,
				'CurrencyForm',
				$fields,
				$actions
			);
		}
	}

	public function setCurrency($data, $form) {

		$data = Convert::raw2sql($data);
		$currency = isset($data['Currency']) ? $data['Currency'] : null;

		$config = ShopConfig::current_shop_config();
		$exchangeRates = $config->ExchangeRates();
		$currencies = $exchangeRates->column('Currency');

		if (in_array($currency, $currencies)) {
			Session::set('SWS.Currency', $currency);


			//
		}
		$this->owner->redirectBack();
	}
}

class ExchangeRate_Extension extends DataExtension {

	public function updatePrice($amount) {

		if ($currency = Session::get('SWS.Currency')) {

			//Get the exchange rate, alter the amount
			$rate = ExchangeRate::get()
				->where("\"Currency\" = '$currency'")
				->limit(1)
				->first();

			if ($rate && $rate->exists()) {
				$amount->setAmount($amount->getAmount() * $rate->Rate);
				$amount->setCurrency($rate->Currency);
				$amount->setSymbol($rate->CurrencySymbol);
			}
		}
	}
}

class ExchangeRate_OrderExtension extends DataExtension {

	private static $db = array(
		'Currency' => 'Varchar(3)',
		'CurrencySymbol' => 'Varchar(10)',
		'ExchangeRate' => 'Decimal(19,4)',
	);

	public function onBeforePayment() {

		//Set the currency for Order from the session
		if ($currency = Session::get('SWS.Currency')) {

			//Get the exchange rate, alter the amount
			$rate = ExchangeRate::get()
				->where("\"Currency\" = '$currency'")
				->limit(1)
				->first();

			if ($rate && $rate->exists()) {
				$this->owner->Currency = $rate->Currency;
				$this->owner->CurrencySymbol = $rate->CurrencySymbol;
				$this->owner->ExchangeRate = $rate->Rate;
			}
		}
		else { //Currency has not been set in the session, assume base currency
			$shopConfig = ShopConfig::current_shop_config();

			$this->owner->Currency = $shopConfig->BaseCurrency;
			$this->owner->CurrencySymbol = $shopConfig->BaseCurrencySymbol;
			$this->owner->ExchangeRate = 1.0; //1 to 1 exchange rate
		}
		$this->owner->write();
	}

	public function updatePrice($amount) {

		//Old orders that do not have the currency set, do not want to use session currency
		//Only if the order is not processed do we want to use the session currency

		//If the exchange rate is saved to the Order use that
		if ($this->owner->Status != 'Cart') {

			if ($this->owner->Currency && $this->owner->ExchangeRate) {
				$amount->setAmount($amount->getAmount() * $this->owner->ExchangeRate);
				$amount->setCurrency($this->owner->Currency);
				$amount->setSymbol($this->owner->CurrencySymbol);
			}
		}
		else if ($currency = Session::get('SWS.Currency')) {

			//Get the exchange rate, alter the amount
			$rate = ExchangeRate::get()
				->where("\"Currency\" = '$currency'")
				->limit(1)
				->first();

			if ($rate && $rate->exists()) {
				$amount->setAmount($amount->getAmount() * $rate->Rate);
				$amount->setCurrency($rate->Currency);
				$amount->setSymbol($rate->CurrencySymbol);
			}
		}
	}
}

class ExchangeRate_OrderRelatedExtension extends DataExtension {

	public function updatePrice($amount) {

		//If the order is processed and the currency saved, use that
		//If the order is processed and no currency saved, do nothing
		//If the order is not processed and the currency in Session, use that

		$order = $this->owner->Order();

		if ($order->Status != 'Cart') {

			if ($order->Currency && $order->ExchangeRate) {
				$amount->setAmount($amount->getAmount() * $order->ExchangeRate);
				$amount->setCurrency($order->Currency);
				$amount->setSymbol($order->CurrencySymbol);
			}
		}
		else if ($currency = Session::get('SWS.Currency')) {

			//Get the exchange rate, alter the amount
			$rate = ExchangeRate::get()
				->where("\"Currency\" = '$currency'")
				->limit(1)
				->first();

			if ($rate && $rate->exists()) {
				$amount->setAmount($amount->getAmount() * $rate->Rate);
				$amount->setCurrency($rate->Currency);
				$amount->setSymbol($rate->CurrencySymbol);
			}
		}
	}
}
