<?php

namespace App\Services\ConfigTransfer;

/** A file that cannot be imported (wrong format, damaged, from the future…). The message is shown to the admin as is. */
class ConfigTransferException extends \RuntimeException
{
}
