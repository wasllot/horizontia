<?php

namespace App\Services\Events;

/** La compra no puede continuar; el mensaje es el estado (sold_out, ended, quantity…). */
class EventSalesException extends \RuntimeException
{
}
