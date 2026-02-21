<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>取引完了通知</title>
</head>
<body style="margin:0; padding:24px; font-family: Arial, 'Hiragino Kaku Gothic ProN', 'Yu Gothic', Meiryo, sans-serif; color:#222; background:#f8f8f8;">
    <div style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #e5e5e5; border-radius:8px; padding:24px;">
        <h1 style="margin:0 0 16px; font-size:20px; line-height:1.4;">
            取引完了のお知らせ
        </h1>

        <p style="margin:0 0 12px; line-height:1.8;">
            {{ $transaction->seller->name ?? '出品者様' }}<br>
            購入者が取引を完了しました。
        </p>

        <hr style="border:none; border-top:1px solid #e5e5e5; margin:16px 0;">

        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <tr>
                <th style="text-align:left; padding:8px 0; width:140px;">取引ID</th>
                <td style="padding:8px 0;">{{ $transaction->id }}</td>
            </tr>
            <tr>
                <th style="text-align:left; padding:8px 0;">商品名</th>
                <td style="padding:8px 0;">{{ $transaction->product->name ?? '商品名未設定' }}</td>
            </tr>
            <tr>
                <th style="text-align:left; padding:8px 0;">購入者</th>
                <td style="padding:8px 0;">{{ $transaction->buyer->name ?? '購入者名未設定' }}</td>
            </tr>
            <tr>
                <th style="text-align:left; padding:8px 0;">取引完了日時</th>
                <td style="padding:8px 0;">
                    {{ optional($transaction->buyer_completed_at)->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s') }}
                </td>
            </tr>
        </table>

        <hr style="border:none; border-top:1px solid #e5e5e5; margin:16px 0;">

        <p style="margin:0; line-height:1.8;">
            取引チャット画面にアクセスして、内容をご確認ください。
        </p>
    </div>
</body>
</html>