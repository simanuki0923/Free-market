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
    @if (session('status'))
      <p class="alert alert-success">{{ session('status') }}</p>
    @endif
    <h1>決済が完了しました！</h1>
    <a href="{{ route('item') }}" class="btn btn-primary">トップに戻る</a>
  </div>
</body>
</html>
