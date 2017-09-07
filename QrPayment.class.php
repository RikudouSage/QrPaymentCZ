<?php

namespace rikudou\CzQrPayment;

use function class_exists;
use Endroid\QrCode\QrCode;

/**
 * Class QrPayment
 * @package rikudou\CzQrPayment
 */
class QrPayment {

  /** @var  int|string $account */
  private $account;
  /** @var  int $bank */
  private $bank;

  /** @var int $variable_symbol */
  public $variable_symbol;
  /** @var  int $specific_symbol */
  public $specific_symbol;
  /** @var  int $constant_symbol */
  public $constant_symbol;
  /** @var string $currency */
  public $currency = "CZK";
  /** @var string $comment */
  public $comment = "";
  /** @var int $repeat */
  public $repeat = 7;
  /** @var  int */
  public $internal_id;
  /** @var  string $due_date */
  public $due_date;
  /** @var float $ammount */
  public $amount;
  /** @var string $country */
  public $country = 'CZ';

  /**
   * QrPayment constructor.
   * Sets account and bank. Allows to specify options in array in format:
   * property_name => value
   *
   * @param int|string $account
   * @param int|string $bank
   * @param array $options
   */
  public function __construct($account, $bank, array $options = null) {
    $this->account = $account;
    $this->bank = $bank;

    if ($options) {
      $this->setOptions($options);
    }
  }

  /**
   * Specifies options in array in format:
   * property_name => value
   *
   * Throws exception if any of the fields contains asterisk symbol
   *
   * @param array $options
   * @throws \rikudou\CzQrPayment\QrException
   */
  public function setOptions(array $options) {
    foreach ($options as $key => $value) {
      if (property_exists($this, $key)) {
        $this->$key = $value;
      }
    }

    $this->checkProperties();
  }

  /**
   * Converts account and bank numbers to IBAN
   * @return string
   */
  public function accToIBAN() {
    $this->country = strtoupper($this->country);

    $part1 = ord($this->country[0]) - ord('A') + 10;
    $part2 = ord($this->country[1]) - ord('A') + 10;

    $numeric = sprintf("%04d%016d%d%d00", $this->bank, $this->account, $part1, $part2);

    $mod = "";
    foreach (str_split($numeric) as $n) {
      $mod = ($mod . $n) % 97;
    }

    return sprintf("%.2s%02d%04d%016d", $this->country, 98 - $mod, $this->bank, $this->account);
  }

  /**
   * Returns QR Payment string
   * Throws exception if any of the fields contains asterisk symbol
   * or if the date is not in format understandable by strtotime() function
   *
   * @return string
   * @throws \rikudou\CzQrPayment\QrException
   */
  public function getQrString() {
    $this->checkProperties();

    $qr = "SPD*1.0*";
    $qr .= sprintf("ACC:%s*", $this->accToIBAN());
    $qr .= sprintf("AM:%.2f*", $this->amount);
    $qr .= sprintf("CC:%s*", strtoupper($this->currency));

    if ($this->repeat) {
      $qr .= sprintf("X-PER:%d*", $this->repeat);
    }
    if ($this->comment) {
      $qr .= sprintf("MSG:%.60s*", $this->comment);
    }
    if ($this->internal_id) {
      $qr .= sprintf("X-ID:%d*", $this->internal_id);
    }
    if ($this->variable_symbol) {
      $qr .= sprintf("X-VS:%d*", $this->variable_symbol);
    }
    if ($this->specific_symbol) {
      $qr .= sprintf("X-SS:%d*", $this->specific_symbol);
    }
    if ($this->constant_symbol) {
      $qr .= sprintf("X-KS:%d*", $this->constant_symbol);
    }
    if (($dueDate = $this->getDueDate())) {
      $qr .= sprintf("DT:%s*", $dueDate->format('Ymd'));
    }

    return substr($qr,0,-1);
  }

  /**
   * Checks whether the due date is set.
   * Throws exception if the date format cannot be parsed by strtotime() func
   *
   * @return bool
   * @throws \rikudou\CzQrPayment\QrException
   */
  private function getDueDate() {
    if (!$this->due_date) {
      return null;
    }

    if (!$this->due_date instanceof \DateTime && !@strtotime($this->due_date)) {
      throw new QrException("Error: Due date value ($this->due_date) cannot be transformed, you must ensure that the due date value is acceptable by strtotime()", QrException::ERR_DATE);
    }

    return $this->due_date instanceof \DateTime ? $this->due_date : new \DateTime($this->due_date);
  }

  /**
   * Checks all properties for asterisk and throws exception if asterisk
   * is found
   * @throws \rikudou\CzQrPayment\QrException
   */
  private function checkProperties() {
    foreach (get_object_vars($this) as $property => $value) {
      if (strpos($value,"*") !== false) {
        throw new QrException("Error: properties cannot contain asterisk (*). Property $property contains it.", QrException::ERR_ASTERISK);
      }
    }
  }

  /**
   * Return QrCode object with QrString set, for more info see Endroid QrCode
   * documentation
   *
   * @param bool $set_png_header
   * @return \Endroid\QrCode\QrCode
   */
  public function getQr($set_png_header = false) {
    if (!class_exists("Endroid\QrCode\QrCode")) {
      throw new QrException("Error: library Endroid\QrCode is not loaded.", QrException::ERR_MISSING_LIBRARY);
    }

    if ($set_png_header) {
      header("Content-type: image/png");
    }

    $qr = new QrCode;
    return $qr->setText($this->getQrString());
  }

}