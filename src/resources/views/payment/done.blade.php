<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>COACHTECH</title>
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  <link rel="stylesheet" href="{{ asset('css/done.css') }}">
</head>
<body>
  <div class="container">

    @php
      $pm = session('payment_method'); // "credit_card" or "convenience_store" 等
    @endphp

    {{-- 見出しと説明テキストを支払い方法ごとに出し分け --}}
    @if ($pm === 'convenience_store')
      <h1>コンビニ支払いの受付が完了しました！</h1>
      <p style="font-size:16px; line-height:1.6; margin-bottom:40px;">
        商品はあなたの購入分として確保されました。<br>
        まだお支払いは完了していません。<br>
        コンビニでのお支払いをお願いします。
      </p>
    @elseif ($pm === 'credit_card')
      <h1>決済が完了しました！</h1>
      <p style="font-size:16px; line-height:1.6; margin-bottom:40px;">
        クレジットカードでのお支払いが正常に完了しました。<br>
        ご利用ありがとうございました。
      </p>
    @else
      {{-- フォールバック（万が一payment_methodが来なかった場合） --}}
      <h1>購入手続きが完了しました！</h1>
      <p style="font-size:16px; line-height:1.6; margin-bottom:40px;">
        処理が完了しました。
      </p>
    @endif

    <a href="{{ route('item') }}" class="btn btn-primary">トップに戻る</a>
  </div>
</body>
</html>
