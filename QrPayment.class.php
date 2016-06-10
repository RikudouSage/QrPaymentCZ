<?php

namespace rikudou\CzQrPayment;

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
  public $comment = "QR Payment";
  /** @var int $repeat */
  public $repeat = 7;
  /** @var  int */
  public $internal_id;
  /** @var  string $due_date */
  public $due_date;
  /** @var float $ammount */
  public $amount;

  /**
   * QrPayment constructor.
   * Sets account and bank. Allows to specify options in array in format:
   * property_name => value
   *
   * @param int $account
   * @param int $bank
   * @param array $options
   */
  public function __construct($account, $bank, $options = []) {
    $this->account = $account;
    $this->bank = $bank;
    if($options) {
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
  public function setOptions($options) {
    foreach ($options as $key => $value) {
      if(property_exists($this, $key)) {
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
    $bank = $this->bank;
    $account = $this->account;
    $part = [];
    $replacements = [];
    $replacements[1] = [];
    $replacements[2] = [];

    $base = sprintf("CZ00{$bank}%016d", $account);
    $part[1] = substr($base, 0, 4);
    $part[2] = substr($base, 4);
    $reversed = $part[2] . $part[1];
    for ($i = 10; $i <= 35; $i++) {
      $replacements[1][] = $i;
    }
    for ($j = "A"; $j <= "Z"; $j++) {
      $replacements[2][] = $j;
      if($j == "Z") {
        break;
      }
    }
    $numeric = str_replace($replacements[2], $replacements[1], $reversed);
    $mod = bcmod(strval($numeric), 97);
    $control = 98 - $mod;
    $final = sprintf("CZ$control{$bank}%016d", $account);
    return $final;
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
    $iban = $this->accToIBAN();
    $qr .= "ACC:$iban*";
    $qr .= "AM:$this->amount*";
    $qr .= "CC:$this->currency*";
    $qr .= "MSG:$this->comment*";
    $qr .= "X-PER:$this->repeat*";
    if($this->internal_id) {
      $qr .= "X-ID:$this->internal_id*";
    }
    if ($this->variable_symbol) {
      $qr .= "X-VS:$this->variable_symbol*";
    }
    if ($this->specific_symbol) {
      $qr .= "X-SS:$this->specific_symbol*";
    }
    if ($this->constant_symbol) {
      $qr .= "X-KS:$this->constant_symbol*";
    }
    if($this->hasDueDate()) {
      $qr .= "DT:".date("Ymd",strtotime($this->due_date))."*";
    }

    if(substr($qr, -1) == "*") {
      $qr = substr($qr,0,-1);
    }

    return $qr;
  }

  /**
   * Checks whether the due date is set.
   * Throws exception if the date format cannot be parsed by strtotime() func
   * 
   * @return bool
   * @throws \rikudou\CzQrPayment\QrException
   */
  private function hasDueDate() {
    if(!$this->due_date) {
      return false;
    }
    if(!strtotime($this->due_date)) {
      throw new QrException("Error: Due date value ($this->due_date) cannot be transformed, you must ensure that the due date value is acceptable by strtotime()",QrException::ERR_DATE);
    }
    return true;
  }

  /**
   * Checks all properties for asterisk and throws exception if asterisk
   * is found
   * @throws \rikudou\CzQrPayment\QrException
   */
  private function checkProperties() {
    foreach (get_object_vars($this) as $property => $value) {
      if(strpos($value,"*") !== false) {
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
    if($set_png_header) {
      header("Content-type: image/png");
    }
    $qr = new QrCode();
    return $qr->setText($this->getQrString());
  }

}