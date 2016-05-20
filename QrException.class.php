<?php

namespace rikudou\CzQrPayment;

class QrException extends \Exception {
  const ERR_ASTERISK = 1;
  const ERR_DATE = 2;
}