<?php

namespace Modules\LaraPayease\Factories;
use Modules\LaraPayease\Drivers\MercadoPago;
use Modules\LaraPayease\Drivers\Stripe;
use Modules\LaraPayease\Utils\CurrencyUtil;

class PaymentFactory {

   /**
    * @return \Modules\LaraPayease\Drivers\Stripe
    */

    public function stripe(): Stripe{
        return new Stripe();
     }

    /**
     * @return \Modules\LaraPayease\Drivers\MercadoPago
     */

    public function mercadopago(): MercadoPago{
        return new MercadoPago();
    }

     /**
      * @return \Modules\LaraPayease\Utils\CurrencyUtil\supportedCurrencies
      */

     public function supportedCurrencies(): array{
        return CurrencyUtil::$supportedCurrencies;
     }

      /**
       * @return array $supportedGateways
       */
     public function supportedGateways() : array{
        return [
           'stripe' => [
              'keys' => [
                 'stripe_key' => '',
                 'stripe_secret' => '',
              ],
              'status' => 'off',
              'currency' => 'USD',
              'exchange_rate' => '',
              'ipn_url_type' => 'get.success'
          ],
          'mercadopago' => [
              'keys' => [
                 'mp_access_token' => '',
                 'mp_public_key' => '',
                 'mp_webhook_secret' => '',
              ],
              'status' => 'off',
              'currency' => 'MXN',
              'exchange_rate' => '',
              'enable_test_mode' => '',
              'binary_mode' => '',
              'max_installments' => '',   // optional cap of card installments (meses sin intereses are enabled in the Mercado Pago account)
              'ipn_url_type' => 'mercadopago.return'
          ],
        ];
     }

      /**
       * @return string $getIpnUrl
       */
     public function getIpnUrl($method): string {
          $gateWays = $this->supportedGateways();
          if(!empty($gateWays[$method]['ipn_url_type'])) {
              return $gateWays[$method]['ipn_url_type'];
          }
          return '';
     }
}
