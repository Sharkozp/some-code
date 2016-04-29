<?php

/**
 * Created by PhpStorm.
 * User: oleksandr
 * Date: 10/26/15
 * Time: 23:40
 */
class KM_Calculator_Helper_Data extends Mage_Core_Helper_Abstract {
	private $yearsCount = 25;
	private $startDate = "2012-04-01";
	private $feedInDate = "2015-08-01";
	private $feedInCost = 12.34;
	//Verbrauch ohne Batterie ohne EV!B2

	private $feedInIncrease = 0.9975;
	//Markt und Finanzierung!C3
	private $totalInvestmentCost;
	private $totalInvestmentAndStorageCost;
	//Markt und Finanzierung!C7
	private $equity = 5000;
	private $depreciationIndex = 0;
	//Markt und Finanzierung!$C$9
	private $interestOnDebt = 2;
	//Markt und Finanzierung!C8
	private $runTime = 20;
	private $solarYieldLocation;
	//PV Anlage und E-Mobility!C3
	private $powerRequirements;
	//PV Anlage und E-Mobility!C10
	private $sqRoof;
	//PV Anlage und E-Mobility!C14
	private $tilt;
	//PV Anlage und E-Mobility!C13
	private $orientation;
	//PV Anlage und E-Mobility!C12
	private $location;
	private $modules;
	private $modulePower;
	//Markt und Finanzierung!C10
	private $discountRate = 2;
	private $years;
	private $orientationTable = array(
		'N'  => 1,
		'NO' => 2,
		'O'  => 3,
		'SO' => 4,
		'S'  => 5,
		'SW' => 6,
		'W'  => 7,
		'NW' => 8,
	);

	private $angleOfInclination = array(
		0  => array(0.87, 0.87, 0.87, 0.87, 0.87, 0.87, 0.87, 0.87),
		10 => array(0.75, 0.8, 0.9, 0.95, 0.95, 0.95, 0.9, 0.8),
		20 => array(0.7, 0.75, 0.9, 0.95, 0.95, 0.95, 0.9, 0.75),
		30 => array(0.6, 0.65, 0.85, 0.95, 1, 0.95, 0.85, 0.65),
		40 => array(0.5, 0.6, 0.85, 0.9, 0.95, 0.9, 0.85, 0.6),
		50 => array(0.45, 0.55, 0.8, 0.9, 0.95, 0.9, 0.8, 0.55),
	);
	private $locationPrice = array(
		"Baden-Württemberg"      => 1110,
		"Bayern"                 => 1050,
		"Berlin"                 => 970,
		"Bremen"                 => 950,
		"Brandenburg"            => 990,
		"Hamburg"                => 940,
		"Hessen"                 => 1030,
		"Mecklenburg-Vorpommern" => 1030,
		"Niedersachsen"          => 990,
		"Nordrhein-Westfalen"    => 1010,
		"Rheinland-Pfalz"        => 1090,
		"Saarland"               => 1070,
		"Sachsen"                => 1050,
		"Sachsen-Anhalt"         => 1030,
		"Schleswig Holstein"     => 980,
		"Thüringen"              => 1010,
	);

	public function calculate($data) {
		$this->powerRequirements = $data['monthPower'];
		$this->sqRoof            = $data['roofArea'];
		$this->tilt              = $data['roof'];
		$this->orientation       = $data['direction'];
		$this->location          = $data['region'];
		$this->modules           = $data['moduleCount'];
		$endDate                 = new Zend_Date();
		$endDate                 = $endDate->setMonth(4)->setDay(1)->add($this->yearsCount, Zend_Date::YEAR)->get('YYYY-MM-dd');
		$months                  = $this->getMonths($this->startDate, $endDate);
		$this->years             = intval($months / 12);
		//Markt und Finanzierung!C4
		$this->totalInvestmentAndStorageCost = $this->getTotalInvestmentCost() + $this->getBatteryProduct()->getPrice();

		$result        = 0;
		$resultBattery = 0;
		$i             = 0;

		while ($i <= $this->years) {
			if ($i >= $this->years - $this->yearsCount) {
				$result += $this->getAnnualCashFlow($i);
				$resultBattery += $this->getAnnualCashFlow($i, true);
			}
			$i ++;
		}

		$income          = $this->getIncomeFromCurrentInjection($result);
		$proceeds        = $this->getProceedsFromTheSavings($result);
		$incomeBattery   = $this->getIncomeFromCurrentInjection($resultBattery, true);
		$proceedsBattery = $this->getProceedsFromTheSavings($resultBattery, true);

		$coreHelper = Mage::helper('core');
		
		$kmCategory = Mage::helper('km_category');
		return array(
			"resultText"               => $coreHelper->currency(round($result - $this->totalInvestmentCost, 2), true, false),
			"proceeds"                 => $kmCategory->formatPriceWithoutSymbol(round($proceeds, 2)),
			"income"                   => $kmCategory->formatPriceWithoutSymbol(round($income, 2)),
			"investmentCost"           => $kmCategory->formatPriceWithoutSymbol(round($this->totalInvestmentCost, 2)),
			"incomeHeight"             => intval($income * 100 / $result),
			"investmentHeight"         => intval($this->totalInvestmentCost * 100 / $result),
			"resultBatteryText"        => $coreHelper->currency(round($resultBattery - $this->totalInvestmentAndStorageCost, 2), true, false),
			"proceedsBattery"          => $kmCategory->formatPriceWithoutSymbol(round($proceedsBattery, 2)),
			"incomeBattery"            => $kmCategory->formatPriceWithoutSymbol(round($incomeBattery, 2)),
			"investmentAndStorageCost" => $kmCategory->formatPriceWithoutSymbol(round($this->totalInvestmentAndStorageCost, 2)),
			"incomeBatteryHeight"      => intval($incomeBattery * 100 / $resultBattery),
			"investmentBatteryHeight"  => intval($this->totalInvestmentAndStorageCost * 100 / $resultBattery)
		);
	}


	private function getIncomeFromCurrentInjection($sum, $battery = false) {
		$income = $this->getYearlyIncome(0, $battery);

		return ($income / ($this->getAnnualIncome(0, $battery) + $income)) * $sum;
	}

	private function getProceedsFromTheSavings($sum, $battery = false) {
		$income = $this->getAnnualIncome(0, $battery);

		return ($income / ($income + $this->getYearlyIncome(0, $battery))) * $sum;
	}

	//Zwischenkalkulation!B4
	private function getFeedInCost() {
		$feedInCost = $this->feedInCost;
		$today      = Mage::getModel('core/date')->date('Y-m-d ');
		$months     = $this->getMonths($this->feedInDate, $today);
		for ($i = 0; $i < $months; $i ++) {
			$feedInCost = $feedInCost * $this->feedInIncrease;
		}

		return $feedInCost;
	}

	//Zwischenkalkulation!B5
	private function getLevelOfRemuneration() {
		return $this->getFeedInCost() - 0.34;
	}

	//Zwischenkalkulation!B6
	private function getMixedPrice() {
		$generatorPower = $this->getGeneratorPower();

		return ($generatorPower / 10 <= 1) ? $this->getFeedInCost() : ((10 * $this->getFeedInCost()) + ($generatorPower - 10) * $this->getLevelOfRemuneration()) / $generatorPower;
	}

	//Zwischenkalkulation!C33
	private function getGeneratorPower() {
		return $this->sqRoof / 1.6 * $this->getModulePower() / 1000;
	}

	//Zwischenkalkulation!C42
	private function getStepForCorrectionFactor($orientation) {
		return $this->orientationTable[ $orientation ];
	}

	//Zwischenkalkulation!C36
	private function getCorrectionFactorOfTiltAndOrientation($tilt, $orientation) {
		return $this->angleOfInclination[ $tilt ][ $this->getStepForCorrectionFactor($orientation) - 1 ];
	}

	//Zwischenkalkulation!C34
	private function getSolarYieldLocation() {
		$generatorPower      = $this->getGeneratorPower();
		$correctionFactor    = $this->getCorrectionFactorOfTiltAndOrientation($this->tilt, $this->orientation);
		$incomeRelatedSystem = $this->getIncomeRelatedPVSystem($this->location);

		return $generatorPower * $correctionFactor * $incomeRelatedSystem;
	}

	//Zwischenkalkulation!C37
	private function getIncomeRelatedPVSystem($location) {
		return $this->locationPrice[ $location ] * 1.07 * 86 / 100;
	}

	//Zwischenkalkulation!C39
	private function getCorrectedOwnConsumptionShare() {
		//Zwischenkalkulation!C38
		$consumptionAbsolute = 1 / ($this->getSolarYieldLocation() / $this->powerRequirements) * 0.35;

		return ($consumptionAbsolute > 0.9) ? 0.9 : $consumptionAbsolute;
	}

	//Zwischenkalkulation!C40
	private function getPowerConsumptionWithStorage() {
		//Zwischenkalkulation!C39 * 2
		$correctedOwnConsumptionShare = $this->getCorrectedOwnConsumptionShare() * 2;

		return ($correctedOwnConsumptionShare > 0.75) ? 0.75 : $correctedOwnConsumptionShare;
	}

	//Verbrauch ohne Batterie ohne EV!C2
	private function getPricesWithAnnualIncrease($years) {
		$annual = $this->getAnnual();
		$i      = 0;
		while ($i ++ < $years) {
			$annual = ($annual * $this->getPriceIncrease()) + $annual;
		}

		return $annual;
	}

	//Verbrauch ohne Batterie ohne EV!B5
	//TODO remove runs twice
	private function getYearlyYield($years) {
		$i           = 0;
		$yearlyYield = $this->getSolarYieldLocation();
		while ($i ++ < $years) {
			$yearlyYield *= (1 - 0.07 / 100);
		}

		return $yearlyYield;
	}

	//Verbrauch ohne Batterie ohne EV!B6
	private function getConsumptionProfile($years, $battery) {
		$correctedOwnConsumptionShare = ($battery) ? $this->getPowerConsumptionWithStorage() : $this->getCorrectedOwnConsumptionShare();

		return $this->getYearlyYield($years) * $correctedOwnConsumptionShare;
	}

	//Verbrauch ohne Batterie ohne EV!B7
	private function getAnnualIncome($years, $battery = false) {
		if ($battery) {
			if ($this->getConsumptionProfile($years, $battery) > $this->getYearlyYield($years)) {
				$annual = $this->getYearlyYield($years);
			} else {
				$annual = $this->getConsumptionProfile($years, $battery);
			}

			return $annual * $this->getPricesWithAnnualIncrease($years);
		}

		return $this->getConsumptionProfile($years, $battery) * $this->getPricesWithAnnualIncrease($years);
	}

	//Verbrauch ohne Batterie ohne EV!B8
	private function getYearlyIncome($years, $battery) {
		return ($this->getYearlyYield($years) - $this->getConsumptionProfile($years, $battery)) * $this->getMixedPrice() / 100;
	}

	//Verbrauch ohne Batterie ohne EV!B10
	private function getOperatingCosts($years) {
		$operatingCosts = $this->getGeneratorPower() * 15;
		$i              = 0;
		while ($i ++ < $years) {
			$operatingCosts *= (1 + $this->discountRate / 100);
		}

		return $operatingCosts;
	}

	//Verbrauch ohne Batterie ohne EV!B11
	private function getDepreciation($battery) {
		return $this->getTotalInvestmentCost($battery) * $this->depreciationIndex;
	}

	//Verbrauch ohne Batterie ohne EV!B12
	private function getRestCredit($years = 0, $battery) {
		$restCredit = 0;
		$i          = 0;
		while ($i <= $years) {
			if ($i == 0) {
				$restCredit = $this->getTotalInvestmentCost($battery) - $this->equity;
			} else {
				$restCredit -= $this->getAnnualAnnuity($battery);
			}
			if ($restCredit < 0) {
				return 0;
			}
			$i ++;
		}

		return $restCredit;
	}

	//Verbrauch ohne Batterie ohne EV!B13
	private function getBorrowingCosts($years, $battery) {
		return $this->getRestCredit($years, $battery) * $this->interestOnDebt / 100;
	}

	//Verbrauch ohne Batterie ohne EV!B14
	//TODO Check maybe runs twice
	private function getAnnualAnnuity($battery) {
		return $this->getRestCredit(0, $battery) * (($this->interestOnDebt / 100 * (pow((1 + $this->interestOnDebt / 100), $this->runTime)))) / ((pow((1 + $this->interestOnDebt / 100), $this->runTime)) - 1);
	}

	//Verbrauch ohne Batterie ohne EV!B15
	private function getTotalReturns($years, $battery) {
		$totalReturns = 0;
		if ($years == 0) {
			$totalReturns += $this->getTotalInvestmentCost($battery);
		}
		$totalReturns += $this->getOperatingCosts($years) + $this->getBorrowingCosts($years, $battery) + $this->getDepreciation($battery);

		return $totalReturns;
	}

	//Verbrauch ohne Batterie ohne EV!B16
	private function getAnnualCashFlow($years, $battery = false) {
		return $this->getAnnualIncome($years, $battery) + $this->getYearlyIncome($years, $battery) - $this->getTotalReturns($years, $battery);
	}

	private function getTotalInvestmentCost($battery = false) {
		if (! $this->totalInvestmentCost) {
			$inventor    = $this->getInventor($this->modules);
			$totalOutput = $this->getTotalOutput($this->modules);
			$price       = $inventor->getPrice();

			$gestellCost = $this->getGestellProduct()->getPrice();

			//Gestell price * kWp = Kosten für Gestell
			$costOfFrame = $gestellCost * $totalOutput;

			$moduleCost = $this->getModuleProduct()->getPrice();

			//Kosten für Gestell + Modulanzahl * Module Price + Kosten Wechselrichter = Materialkosten
			$this->totalInvestmentCost = $costOfFrame + $this->modules * $moduleCost + $price;
		}

		return ($battery) ? $this->totalInvestmentAndStorageCost : $this->totalInvestmentCost;
	}

	public function getInventor($modules) {
		$totalOutput = $this->getTotalOutput($modules);
		$collection  = Mage::getResourceModel('catalog/product_collection');
		$collection->addAttributeToSelect('*')
		           ->addAttributeToFilter('max_leistungsgrenzen', array("gteq" => $totalOutput))
		           ->addAttributeToFilter('niedrig_leistungsgrenzen', array("lteq" => $totalOutput));

		return $collection->getFirstItem();
	}

	public function getTotalOutput($modules) {
		//Modulanzahl * Modulleistung = Gesamtleistung in kWp
		return $modules * $this->getModulePower() / 1000;
	}
	private function getMonths($startDate, $endDate = null) {
		$startDate = new Zend_Date($startDate);
		$endDate   = new Zend_Date($endDate);
		$now       = ($endDate != null) ? $endDate : Zend_Date::now();

		$startMonth = intval($startDate->get(Zend_Date::MONTH));
		$startYear  = intval($startDate->get(Zend_Date::YEAR));

		$nowMonth = intval($now->get(Zend_Date::MONTH));
		$nowYear  = intval($now->get(Zend_Date::YEAR));
		$months   = 0;
		$count    = true;
		while ($count) {
			if ($startYear != $nowYear) {
				$months += 12 - $startMonth + 1;
				$startMonth = 1;
				$startYear ++;
			}

			if ($startYear == $nowYear) {
				$months += $nowMonth - $startMonth;
				$startMonth = $nowMonth;
			}

			if ($startMonth == $nowMonth && $startYear == $nowYear) {
				$count = false;
			}
		}

		return $months;
	}

	public function getAnnual() {
		return Mage::getStoreConfig('calculation_section/calculator_group/electricity_price_field');
	}

	public function getPriceIncrease() {
		return Mage::getStoreConfig('calculation_section/calculator_group/price_increase_field');
	}

	public function getModulePower() {
		if (! $this->modulePower) {
			$moduleProduct     = $this->getModuleProduct();
			$this->modulePower = $moduleProduct->getModulMacht();
		}

		return $this->modulePower;
	}

	public function getProductByAttributeSet($attrSetName) {
		$attributeSetId = Mage::getModel('eav/entity_attribute_set')
		                      ->load($attrSetName, 'attribute_set_name')
		                      ->getAttributeSetId();

		//Load product model collecttion filtered by attribute set id
		$_productCollection = Mage::getModel('catalog/product')->getCollection()
		                          ->addAttributeToSelect('*')
		                          ->addFieldToFilter('attribute_set_id', $attributeSetId)
		                          ->addAttributeToFilter('status', 1)// enabled
		                          ->addAttributeToFilter('visibility', 4);

		return $_productCollection->getFirstItem();
	}

	public function getModuleProduct() {
		$attrSetName = 'Modul';

		return $this->getProductByAttributeSet($attrSetName);
	}
	public function getBatteryProduct() {
		$attrSetName    = 'Batterie';

		return $this->getProductByAttributeSet($attrSetName);
	}

	public function getGestellProduct() {
		$attrSetName = 'Gestell';

		return $this->getProductByAttributeSet($attrSetName);
	}

	public function getAddToCartLink() {
		return Mage::getUrl('calculator/index/addToCart');
	}

	public function getAttribute($code) {
		return Mage::getResourceModel('catalog/product')->getAttribute("product_type")->getSource()->getOptionId($code);
	}

	public function getAttributeName($product) {
		// Get attribute set id.
		$attributeSetId = $product->getAttributeSetId();
		$attributeSet   =  Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);

		// This is attribute set name.
		return $attributeSet->getAttributeSetName();
	}

	public function getRegions() {
		return array(
			"Baden-Württemberg",
			"Bayern",
			"Berlin",
			"Bremen",
			"Brandenburg",
			"Hamburg",
			"Hessen",
			"Mecklenburg-Vorpommern",
			"Niedersachsen",
			"Nordrhein-Westfalen",
			"Rheinland-Pfalz",
			"Saarland",
			"Sachsen",
			"Sachsen-Anhalt",
			"Schleswig Holstein",
			"Thüringen"
		);
	}
}