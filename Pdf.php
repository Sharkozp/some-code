<?php

/**
 * Created by PhpStorm.
 * User: oleksandr
 * Date: 2/20/16
 * Time: 12:49
 */
require_once 'Library/Pdf/Base.php';

class KM_Calculator_Helper_Pdf extends Mage_Core_Helper_Abstract {
	/**
	 * Y coordinate
	 *
	 * @var int
	 */
	private $y;
	private $x;
	private $post;

	/**
	 * Zend PDF object
	 *
	 * @var Library_Pdf_Base
	 */
	private $pdf;
	private $yellowColor;
	private $greenColor;
	private $blackColor;
	private $blockColor;
	private $font;
	private $fontBold;
	private $battery;

	public function generatePdf($post) {
		$this->y    = 800;
		$this->x    = 40;
		$this->post = $post;
		$this->pdf  = new Library_Pdf_Base();
		$page       = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);

		$this->font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/fonts/roboto/Roboto-Regular.ttf');
		$this->fontBold = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/lib/fonts/roboto/Roboto-Bold.ttf');
		$backgroundColor   = new Zend_Pdf_Color_Html("#f2f2f2");
		$this->yellowColor = new Zend_Pdf_Color_Html("#ffd801");
		$this->greenColor  = new Zend_Pdf_Color_Html("#015426");
		$this->blackColor  = new Zend_Pdf_Color_Html("#000000");
		$this->blockColor  = new Zend_Pdf_Color_Html("#d3ded6");

		$store = Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStore();
		$page->setFillColor($backgroundColor)->drawRectangle(0, 0, 595, 842);

		$this->insertLogo($page, $store);

		$page->setFillColor($this->greenColor)->setFont($this->font, 16)->drawText('Detailübersicht Ihrer Anlage', $this->x, $this->y - 50, 'UTF-8');
		$page->setLineColor($this->yellowColor)->drawLine($this->x - 5, $this->y - 35, $this->x - 5, $this->y - 55);

		$page->setFillColor($this->blackColor)->setFont($this->font, 10);
		$text = "Alle Angaben ohne Gewähr. Es handelt sich hier um eine unverbindliche Beispielberechnung. Unser Fachpartner übernimmt gerne die Detailplanung für Ihre Anlage und gibt Ihnen genauere Informationen über Ihre Ertäge.";
		$this->drawTextArea($page, $text, $this->x, $this->y -= 75, 15, 90);

		$this->setLeftSide($page, $this->font);
		$this->x += 264;
		$this->setRightSide($page, $this->font);

		$this->pdf->pages[] = $page;

		return $this->pdf->render();
	}

	/**
	 * @param Zend_Pdf_Page $page
	 */
	private function setLeftSide($page) {
		$startPoint = $this->x - 5;
		$endPoint   = $this->x + 250;
		$page->setLineColor($this->blockColor);
		$page->setFillColor($this->greenColor)->setFont($this->font, 14)->drawText('Ihrer Angaben', $this->x, $this->y - 100, 'UTF-8');

		$page->setFont($this->font, 12);
		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 115, $endPoint, $this->y - 140);
		$page->setFillColor($this->blackColor)->drawText('Jährlicher Stromverbrauch', $this->x, $this->y - 132, 'UTF-8');
		$page->setFillColor($this->blackColor);
		$this->pdf->drawText($page, "{$this->post['monthPower']} kWh", $this->x + 240, $this->y - 132, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blackColor)->drawText('Ihre Region', $this->x, $this->y - 157, 'UTF-8');
		$this->pdf->drawText($page, $this->post['region'], $this->x + 240, $this->y - 157, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 165, $endPoint, $this->y - 190);
		$page->setFillColor($this->blackColor)->drawText('Geschätzte Dachfläche', $this->x, $this->y - 182);
		$this->pdf->drawText($page, $this->post['roofArea'] . " " . html_entity_decode("m&sup2;"), $this->x + 240, $this->y - 182, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blackColor)->drawText('Dachneigung', $this->x, $this->y - 207, 'UTF-8');
		$this->pdf->drawText($page, $this->post['roof'] . html_entity_decode("&deg;"), $this->x + 240, $this->y - 207, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 215, $endPoint, $this->y - 240);
		$page->setFillColor($this->blackColor)->drawText('Dachausrichtung', $this->x, $this->y - 232, 'UTF-8');
		$this->pdf->drawText($page, $this->post['direction'], $this->x + 240, $this->y - 232, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blackColor)->drawText('Anzahl Module', $this->x, $this->y - 257, 'UTF-8');
		$this->pdf->drawText($page, $this->post['moduleCount'], $this->x + 240, $this->y - 257, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 265, $endPoint, $this->y - 290);
		$page->setFillColor($this->blackColor)->drawText('Gesamtleistung', $this->x, $this->y - 282, 'UTF-8');
		$this->pdf->drawText($page, "{$this->post['modulesPower']} kWp", $this->x + 240, $this->y - 282, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blackColor)->drawText('Inbetriebnahme', $this->x, $this->y - 307, 'UTF-8');
		$this->pdf->drawText($page, Mage::app()->getLocale()->date()->toString("MMMM yyyy"), $this->x + 240, $this->y - 307, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 315, $endPoint, $this->y - 340);
		$page->setFillColor($this->blackColor)->drawText('Berechnungszeitraum', $this->x, $this->y - 332, 'UTF-8');
		$this->pdf->drawText($page, '25 Jahre', $this->x + 240, $this->y - 332, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');

		$page->setFillColor($this->blackColor)->drawText('Strompreis', $this->x, $this->y - 357, 'UTF-8');
		$this->pdf->drawText($page, "1" . " €/kWh", $this->x + 240, $this->y - 357, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT);

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 365, $endPoint, $this->y - 390);
		$page->setFillColor($this->blackColor)->drawText('Mit Speicher?', $this->x, $this->y - 382, 'UTF-8');
		$this->battery = $this->post['battery'];
		$battery = ($this->battery) ? "Yes" : "No";
		$this->pdf->drawText($page, Mage::helper('core')->__($battery), $this->x + 240, $this->y - 382, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');
	}

	/**
	 * @param Zend_Pdf_Page $page
	 */
	private function setRightSide($page) {
		$startPoint = $this->x - 5;
		$endPoint   = $this->x + 250;
		$page->setLineColor($this->blockColor);
		$page->setFillColor($this->greenColor)->setFont($this->font, 14)->drawText('Nach 25 Jahren (Prognose)', $this->x, $this->y - 100, 'UTF-8');


		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 115, $endPoint, $this->y - 152);
		$page->setFillColor($this->blackColor);
		$page->setFont($this->font, 12);
		$this->drawTextArea($page, 'Einnahmen durch Stromeinspeisung', $this->x, $this->y - 132, 12, 25);
		$proceeds = ($this->battery) ? $this->post['proceedsBattery'] : $this->post['proceeds'];
		$this->pdf->drawText($page, $proceeds . " €", $this->x + 240, $this->y - 132, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT);

		$page->setFillColor($this->blackColor);
		$this->drawTextArea($page, 'Ersparnis durch Nutzung des Eigenstroms', $this->x, $this->y - 169, 12, 25);
		$income = ($this->battery) ? $this->post['incomeBattery'] : $this->post['income'];
		$this->pdf->drawText($page, $income . " €", $this->x + 240, $this->y - 169, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT);

		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 189, $endPoint, $this->y - 214);
		$page->setFillColor($this->blackColor);
		$this->drawTextArea($page, 'Investition', $this->x, $this->y - 206, 12, 25);
		$investition = ($this->battery) ? $this->post['investmentAndStorageCost'] : $this->post['investmentCost'];
		$this->pdf->drawText($page, $investition . " €", $this->x + 240, $this->y - 206, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');


		$page->setFillColor($this->greenColor)->setFont($this->font, 14)->drawText('Ihr Gesamtgewinn nach 25 Jahren', $this->x, $this->y - 250, 'UTF-8');
		$page->setFillColor($this->blockColor)->drawRectangle($startPoint, $this->y - 265, $endPoint, $this->y - 290);
		$page->setFillColor($this->blackColor)->setFont($this->font, 12);
		$result = ($this->battery) ? $this->post['resultBattery'] : $this->post['result'];
		$result = str_replace('€', "", $result);
		$this->pdf->drawText($page, $result . " €", $this->x + 240, $this->y - 282, $this->x + 255, Library_Pdf_Base::TEXT_ALIGN_RIGHT, 'UTF-8');
	}

	/**
	 * Insert logo to pdf page
	 *
	 * @param Zend_Pdf_Page $page
	 * @param null $store
	 */
	protected function insertLogo(&$page, $store = null) {
		$this->y = $this->y ? $this->y : 815;
		$image   = Mage::getStoreConfig('sales/identity/logo', $store);
		if ($image) {
			$image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
			if (is_file($image)) {
				$image       = Zend_Pdf_Image::imageWithPath($image);
				$top         = $page->getHeight()-40;
				$widthLimit  = 200; //half of the page width
				$heightLimit = 200; //assuming the image is not a "skyscraper"
				$width       = $image->getPixelWidth();
				$height      = $image->getPixelHeight();

				//preserving aspect ratio (proportions)
				$ratio = $width / $height;
				if ($ratio > 1 && $width > $widthLimit) {
					$width  = $widthLimit;
					$height = $width / $ratio;
				} elseif ($ratio < 1 && $height > $heightLimit) {
					$height = $heightLimit;
					$width  = $height * $ratio;
				} elseif ($ratio == 1 && $height > $heightLimit) {
					$height = $heightLimit;
					$width  = $widthLimit;
				}

				$y1 = $top - $height;
				$y2 = $top;
				$x1 = 440;
				$x2 = $x1 + $width;

				//coordinates after transformation are rounded by Zend
				$page->drawImage($image, $x1, $y1, $x2, $y2);

			//	$this->y = $y1 - 10;
			}
		}
	}

	/**
	 * puts text box to a page
	 *
	 * @param integer $offset_x
	 * @param integer $offset_y
	 */
	public function drawTextArea($page, $text, $pos_x, $pos_y, $height, $length = 0, $offset_x = 0, $offset_y = 0) {
		$x = $pos_x + $offset_x;
		$y = $pos_y + $offset_y;

		if ($length != 0) {
			$text = wordwrap($text, $length, "\n", false);
		}
		$token = strtok($text, "\n");

		while ($token != false) {
			$page->drawText($token, $x, $y);
			$token = strtok("\n");
			$y -= $height;
		}
	}

	public function getCurrencySymbol() {
		$currencySymbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
		if (! $currencySymbol) {
			$currencySymbol = Mage::app()->getStore()->getCurrentCurrencyCode();
		}

		return $currencySymbol;
	}
}