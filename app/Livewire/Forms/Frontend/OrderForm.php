<?php

namespace App\Livewire\Forms\Frontend;

use App\Http\Requests\Student\Order\OrderRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

class OrderForm extends Form
{

    public $user ;
    public string $city             = '';
    public string $firstName        = '';
    public $PayAmount               = '';
    public string $dec              = '';
    public string $lastName         = '';
    public string $company          = '';
    public string $email            = '';
    public string $phone            = '';
    public bool $whatsappOptin      = false;
    public string $state            = '';
    public string $address          = '';
    public string $country          = '';
    public string $zipcode          = '';
    public $totalAmount;
    public $walletBalance           = '';
    public $useWalletBalance        = false;
    public $paymentMethod           = '';
    public $max_dec                 = 800;
    public $countryName;
    public $currency;




    private ?OrderRequest $OrderRequest = null;

    public function boot()
    {
        $this->user           = Auth::user();
        $this->OrderRequest   = new OrderRequest();
        $this->currency       = setting('_general.currency') ?? '';
    }

    public function rules(): array
    {

        // `amount` belongs to the API request; the form's amount is always derived server-side from the cart.
        $rules = collect($this->OrderRequest->rules())->except('amount');

        // A digital-only academy needs no postal address: name, e-mail and payment method are enough.
        if (isPrivateAcademy()) {
            $rules = $rules->merge([
                'phone'   => ($this->whatsappOptin ? 'required|' : 'nullable|') . 'string|max:30',
                'whatsappOptin' => 'boolean',
                'country' => 'nullable|string|max:150',
                'state'   => 'nullable|string|max:255',
                'city'    => 'nullable|string|max:150',
                'zipcode' => 'nullable|string|max:10',
            ]);
        }

        return $rules->all();
    }

    public function messages(): array
    {

        return $this->OrderRequest->messages();
    }

    public function setInfo($billigDetail)
    {
        $this->firstName     = $billigDetail?->first_name ?? '';
        $this->lastName      = $billigDetail?->last_name ?? '';
        $this->company       = $billigDetail?->company ?? '';
        $this->email         = $billigDetail?->email ?? '';
        $this->phone         = $billigDetail?->phone ?? '';
        $this->paymentMethod = $billigDetail?->payment_method ?? '';
    }

    public function setUserAddress($address,$isState = true)
    {
        $this->country      = $address?->country_id ?? '';
        $this->city         = $address?->city ?? '';
        $this->state        = $isState ? $address?->state : '';
        $this->zipcode      = $address?->zipcode ?? '';
    }

    public function updateInfo()
    {
        $billingDetail = [
            'user_id'                   => $this->user->id,
            'first_name'                => $this->firstName,
            'unique_payment_id'         => Str::uuid(),
            'amount'                    => $this->totalAmount,
            'currency'                  => $this->currency,
            'used_wallet_amt'           => $this->useWalletBalance ? $this->walletBalance : 0,
            'last_name'                 => $this->lastName,
            'company'                   => $this->company,
            'email'                     => $this->email,
            'phone'                     => $this->phone,
            'country'                   => $this->country,
            'state'                     => $this->state ,
            'city'                      => $this->city  ,
            'postal_code'               => $this->zipcode,
            'payment_method'            => $this->paymentMethod,
            'description'               => $this->dec,
        ];
        $this->validate($this->rules(), $this->messages());

        return $billingDetail;

    }


}
