<html>
<head>
    <title> {{__('Stripe Payment Gateway')}}</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<div class="stripe-payment-wrapper">
    <div class="srtipe-payment-inner-wrapper">
        <input type="hidden" name="order_id" id="order_id_input" value="{{$stripe_data['order_id']}}"/>
        <form id="stripe_form">
            <input type="hidden" name="order_id" value="{{$stripe_data['order_id']}}"/>
            <input type="hidden" name="cancel_url" value="{{$stripe_data['cancel_url']}}"/>
        </form>
        <div class="btn-wrapper">
            <button id="payment_submit_btn"></button>
        </div>
    </div>
</div>

<script>
    (function($){
        "use strict";
        // Create a Stripe client
        var stripe = Stripe("{{$stripe_data['stripe_key']}}");
        var orderID = document.getElementById('order_id_input').value;
        var submitBtn = document.getElementById('payment_submit_btn');

        document.addEventListener('DOMContentLoaded',function (){
            submitBtn.dispatchEvent(new Event('click'));
        },false);


        submitBtn.addEventListener('click', function () {
            // Create a new Checkout Session using the server-side endpoint you
            submitBtn.innerText = "{{__('Redirecting..')}}"
            submitBtn.disabled = true;
            var form = document.getElementById('stripe_form');
            var formData = new FormData(form);
            // created in step 3.
            fetch("{{route('payease.stripe')}}", {
                headers: {
                    "X-CSRF-TOKEN" : "{{csrf_token()}}",
                },
                method: 'POST',
                processData:false,
                contentType: false,
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (session) {
                    if(session.hasOwnProperty('msg')){
                        alert(session.msg);
                        //redirect to cancel page ==
                        window.location = document.querySelector('input[name="cancel_url"]').value;
                    }
                    return stripe.redirectToCheckout({sessionId: session.id});
                })
                .then(function (result) {
                    // If `redirectToCheckout` fails due to a browser or network
                    // error, you should display the localized error message to your
                    // customer using `error.message`.
                    if (result.error) {
                        alert(result.error.message);
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                });
        });
    })();
</script>
</body>
</html>
