<?php

namespace rikudou\CzQrPayment;

class QrException extends \Exception {
  const ERR_ASTERISK = 1;
  const ERR_DATE = 2;
  const ERR_MISSING_LIBRARY = 3;
}