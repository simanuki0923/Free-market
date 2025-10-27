<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>カード決済確認</title>

  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/create.css') }}">
</head>
<body>
<div class="container">

  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if (session('flash_alert'))
    <div class="alert alert-danger">{{ session('flash_alert') }}</div>
  @endif

  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <section class="p-5">
    <article class="col-6 card">
      <header class="card-header">Stripe決済</header>

      <div class="card-body">

        <form id="card-form" action="{{ route('payment.store') }}" method="POST">
          @csrf

          <input type="hidden" name="product_id" value="{{ $product->id }}">
          <input type="hidden" name="payment_intent_id" id="payment_intent_id" value="">
          <input type="hidden" name="payment_method" value="{{ $payment_method }}">

          <div class="form-group">
            <label>カード番号</label>
            <div id="card-number" class="form-control"></div>
          </div>

          <div class="form-group">
            <label>有効期限</label>
            <div id="card-expiry" class="form-control"></div>
          </div>

          <div class="form-group">
            <label>セキュリティコード</label>
            <div id="card-cvc" class="form-control"></div>
          </div>

          <div id="card-errors" class="text-danger" aria-live="polite"></div>

          <div class="button-group mt-3">
            <button class="btn btn-primary" type="submit" id="pay-btn">支払い</button>
          </div>
        </form>

      </div>
    </article>
  </section>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
  const stripe = Stripe("{{ $publicKey }}");
  const elements = stripe.elements();

  const cardNumber = elements.create('cardNumber');
  const cardExpiry = elements.create('cardExpiry');
  const cardCvc    = elements.create('cardCvc');

  cardNumber.mount('#card-number');
  cardExpiry.mount('#card-expiry');
  cardCvc.mount('#card-cvc');

  const form        = document.getElementById('card-form');
  const btn         = document.getElementById('pay-btn');
  const errEl       = document.getElementById('card-errors');
  const clientSecret = "{{ $clientSecret }}";
  const intentInput  = document.getElementById('payment_intent_id');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    btn.disabled = true;
    errEl.textContent = '';

    const { paymentIntent, error } = await stripe.confirmCardPayment(clientSecret, {
      payment_method: { card: cardNumber }
    });

    if (error) {
      errEl.textContent = error.message || '決済に失敗しました';
      btn.disabled = false;
      return;
    }

    const okStatuses = ['succeeded', 'requires_capture', 'processing'];

    if (paymentIntent && okStatuses.includes(paymentIntent.status)) {
      intentInput.value = paymentIntent.id;
      form.submit();
    } else {
      errEl.textContent = '決済が完了しませんでした（status: ' + (paymentIntent?.status || 'unknown') + '）';
      btn.disabled = false;
    }
  });
</script>
</body>
</html>
