<?php

namespace rikudou\CzQrPayment;

use Endroid\QrCode\QrCode;

/**
 * Class QrPayment
 * @package rikudou\CzQrPayment
 */
class QrPayment
{

    /** @var  int|string $account */
    protected $account;
    /** @var  int $bank */
    protected $bank;

    /** @var int $variableSymbol */
    public $variableSymbol;
    /** @var int $specificSymbol */
    public $specificSymbol;
    /** @var int $constantSymbol */
    public $constantSymbol;
    /** @var string $currency */
    public $currency = "CZK";
    /** @var string $comment */
    public $comment = "";
    /** @var int $repeat */
    public $repeat = 7;
    /** @var string $internalId */
    public $internalId;
    /** @var string|\DateTime $dueDate */
    public $dueDate;
    /** @var float $amount */
    public $amount;
    /** @var string $country */
    public $country = 'CZ';
    /** @var string|null $iban */
    protected $iban = null;
    /** @var string $payeeName */
    public $payeeName;

    /**
     * QrPayment constructor.
     * Sets account and bank. Allows to specify options in array in format:
     * property_name => value
     *
     * @param int|string $account
     * @param int|string $bank
     * @param array $options
     *
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    public function __construct($account, $bank, array $options = null)
    {
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
     * @return $this
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $this->checkProperties();
        return $this;
    }

    /**
     * Converts account and bank numbers to IBAN
     * @throws \rikudou\CzQrPayment\QrPaymentException
     * @return string
     */
    public function getIBAN(): string
    {
        $this->checkProperties();
        if (!is_null($this->iban)) {
            return $this->iban;
        }
        $this->country = strtoupper($this->country);

        $part1 = ord($this->country[0]) - ord('A') + 10;
        $part2 = ord($this->country[1]) - ord('A') + 10;

        $accountPrefix = 0;
        $accountNumber = $this->account;
        if (strpos($accountNumber, '-') !== false) {
            $accountParts = explode('-', $accountNumber);
            $accountPrefix = $accountParts[0];
            $accountNumber = $accountParts[1];
        }

        $numeric = sprintf('%04d%06d%010s%d%d00', $this->bank, $accountPrefix, $accountNumber, $part1, $part2);

        $mod = "";
        foreach (str_split($numeric) as $n) {
            $mod = ($mod . $n) % 97;
        }

        $this->iban = sprintf("%.2s%02d%04d%06d%010s", $this->country, 98 - $mod, $this->bank, $accountPrefix, $accountNumber);
        return $this->iban;
    }

    /**
     * Returns QR Payment string
     * Throws exception if any of the fields contains asterisk symbol
     * or if the date is not in format understandable by strtotime() function
     *
     * @return string
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    public function getQrString(): string
    {
        $this->checkProperties();

        $qr = "SPD*1.0*";
        $qr .= sprintf("ACC:%s*", $this->getIBAN());
        $qr .= sprintf("AM:%.2F*", $this->amount);
        $qr .= sprintf("CC:%s*", strtoupper($this->currency));

        if ($this->repeat) {
            $qr .= sprintf("X-PER:%d*", $this->repeat);
        }
        if ($this->comment) {
            $qr .= sprintf("MSG:%.60s*", $this->comment);
        }
        if ($this->internalId) {
            $qr .= sprintf("X-ID:%s*", $this->internalId);
        }
        if ($this->variableSymbol) {
            $qr .= sprintf("X-VS:%d*", $this->variableSymbol);
        }
        if ($this->specificSymbol) {
            $qr .= sprintf("X-SS:%d*", $this->specificSymbol);
        }
        if ($this->constantSymbol) {
            $qr .= sprintf("X-KS:%d*", $this->constantSymbol);
        }
        if ($this->payeeName) {
            $qr .= sprintf("RN:%s*", $this->payeeName);
        }
        if (($dueDate = $this->getDueDate())) {
            $qr .= sprintf("DT:%s*", $dueDate->format('Ymd'));
        }

        return substr($qr, 0, -1);
    }

    /**
     * Checks whether the due date is set.
     * Throws exception if the date format cannot be parsed by strtotime() func
     *
     * @return \DateTime|null
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    protected function getDueDate(): ?\DateTime
    {
        if (!$this->dueDate) {
            return null;
        }

        if (!$this->dueDate instanceof \DateTime && (!is_string($this->dueDate) || !@strtotime($this->dueDate))) {
            throw new QrPaymentException("Error: Due date value cannot be transformed, you must ensure that the due date value is acceptable by strtotime()", QrPaymentException::ERR_DATE);
        }

        return $this->dueDate instanceof \DateTime ? $this->dueDate : new \DateTime($this->dueDate);
    }

    /**
     * Checks all properties for asterisk and throws exception if asterisk
     * is found
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    protected function checkProperties(): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            if ($property !== "dueDate" && strpos($value, "*") !== false) {
                throw new QrPaymentException("Error: properties cannot contain asterisk (*). Property $property contains it.", QrPaymentException::ERR_ASTERISK);
            }
        }
    }

    /**
     * Return QrCode object with QrString set, for more info see Endroid QrCode
     * documentation
     *
     * @param bool $setPngHeader
     * @return \Endroid\QrCode\QrCode
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    public function getQrImage(bool $setPngHeader = false): QrCode
    {
        if (!class_exists("Endroid\QrCode\QrCode")) {
            throw new QrPaymentException("Error: library endroid/qr-code is not loaded.", QrPaymentException::ERR_MISSING_LIBRARY);
        }

        if ($setPngHeader) {
            header("Content-type: image/png");
        }

        return new QrCode($this->getQrString());
    }

    /**
     * @param string $iban
     *
     * @return static
     * @throws \rikudou\CzQrPayment\QrPaymentException
     */
    public static function fromIBAN(string $iban)
    {
        $instance = new static(0, 0);
        $instance->iban = $iban;
        return $instance;
    }

    /**
     * @param int $variableSymbol
     * @return QrPayment
     */
    public function setVariableSymbol(int $variableSymbol)
    {
        $this->variableSymbol = $variableSymbol;
        return $this;
    }

    /**
     * @param int $specificSymbol
     * @return QrPayment
     */
    public function setSpecificSymbol(int $specificSymbol)
    {
        $this->specificSymbol = $specificSymbol;
        return $this;
    }

    /**
     * @param int $constantSymbol
     * @return QrPayment
     */
    public function setConstantSymbol(int $constantSymbol)
    {
        $this->constantSymbol = $constantSymbol;
        return $this;
    }

    /**
     * @param string $currency
     * @return QrPayment
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @param string $comment
     * @return QrPayment
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @param int $repeat
     * @return QrPayment
     */
    public function setRepeat(int $repeat)
    {
        $this->repeat = $repeat;
        return $this;
    }

    /**
     * @param string $internalId
     * @return QrPayment
     */
    public function setInternalId(string $internalId)
    {
        $this->internalId = $internalId;
        return $this;
    }

    /**
     * @param \DateTime|string $dueDate
     * @return QrPayment
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    /**
     * @param float $amount
     * @return QrPayment
     */
    public function setAmount(float $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @param string $country
     * @return QrPayment
     */
    public function setCountry(string $country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getPayeeName(): string
    {
        return $this->payeeName;
    }

    /**
     * @param string $payeeName
     * @return QrPayment
     */
    public function setPayeeName(string $payeeName): QrPayment
    {
        $this->payeeName = $payeeName;
        return $this;
    }
}
