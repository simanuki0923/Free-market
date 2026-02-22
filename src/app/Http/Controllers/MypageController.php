<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Sell;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionRating;

class MypageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_if(!$user, 403);

        $raw = strtolower((string) $request->query('page', 'sell'));
        $tab = in_array($raw, ['sell', 'buy', 'trading'], true) ? $raw : 'sell';

        $perPage = 24;

        // =========================
        // 出品した商品
        // =========================
        $mySells = Sell::query()
            ->where('user_id', $user->id)
            ->with(['product.category'])
            ->latest('id')
            ->paginate($perPage, ['*'], 'p1')
            ->appends([
                'page' => 'sell',
                'p2'   => $request->query('p2'),
                'p3'   => $request->query('p3'),
            ]);

        // =========================
        // 購入した商品
        // =========================
        $purchasedProducts = Product::query()
            ->join('sells', 'sells.product_id', '=', 'products.id')
            ->join('purchases', 'purchases.sell_id', '=', 'sells.id')
            ->where('purchases.user_id', $user->id)
            ->orderByDesc('purchases.purchased_at')
            ->select([
                'products.id',
                'products.name',
                'products.image_path',
                'products.is_sold',
                'purchases.amount as purchased_amount',
                'purchases.purchased_at',
            ])
            ->paginate($perPage, ['*'], 'p2')
            ->appends([
                'page' => 'buy',
                'p1'   => $request->query('p1'),
                'p3'   => $request->query('p3'),
            ]);

        // =========================
        // 取引中の商品（Transactionベース）
        // =========================
        $tradingQuery = Transaction::query()
            ->with(['product'])
            ->withUnreadCountFor((int) $user->id) // ← 未読件数付与
            ->where(function ($q) use ($user) {
                // 自分が出品者 or 購入者の取引
                $q->where('seller_id', $user->id)
                  ->orWhere('buyer_id', $user->id);
            });

        // ステータス判定（仕様対応）
        // - 購入者: buyer_completed は取引中一覧から除外
        // - 出品者: buyer_completed を取引中一覧に含める（評価導線）
        if (Schema::hasColumn('transactions', 'status')) {
            $tradingQuery->where(function ($q) use ($user) {
                // 購入者として見ている取引
                $q->where(function ($buyerQ) use ($user) {
                    $buyerQ->where('buyer_id', $user->id)
                           ->whereIn('status', ['ongoing', 'trading']);
                })
                // 出品者として見ている取引
                ->orWhere(function ($sellerQ) use ($user) {
                    $sellerQ->where('seller_id', $user->id)
                            ->whereIn('status', ['ongoing', 'trading', 'buyer_completed']);
                });
            });
        } elseif (Schema::hasColumn('transactions', 'completed_at')) {
            // 旧構成（statusカラムなし）
            $tradingQuery->whereNull('completed_at');
        } elseif (Schema::hasColumn('transactions', 'is_completed')) {
            $tradingQuery->where('is_completed', 0);
        }

        // 並び順（last_message_at を優先）
        if (Schema::hasColumn('transactions', 'last_message_at')) {
            $tradingQuery->orderByDesc('last_message_at')
                ->orderByDesc('id');
        } elseif (Schema::hasColumn('transactions', 'updated_at')) {
            $tradingQuery->orderByDesc('updated_at')
                ->orderByDesc('id');
        } else {
            $tradingQuery->orderByDesc('id');
        }

        $tradingProducts = $tradingQuery
            ->paginate($perPage, ['*'], 'p3')
            ->appends([
                'page' => 'trading',
                'p1'   => $request->query('p1'),
                'p2'   => $request->query('p2'),
            ]);

        // 取引中タブに表示する未読総数（任意）
        $tradingUnreadTotal = 0;
        foreach ($tradingProducts as $transaction) {
            $tradingUnreadTotal += (int) ($transaction->unread_messages_count ?? 0);
        }

        // =========================
        // 受けた評価の平均値・件数
        // =========================
        $ratingSummary = TransactionRating::query()
            ->where('ratee_user_id', $user->id)
            ->whereNotNull('rating')
            ->selectRaw('COUNT(*) as rating_count, AVG(rating) as rating_avg')
            ->first();

        $ratingCount = (int) ($ratingSummary->rating_count ?? 0);
        $ratingAverage = $ratingCount > 0
            ? round((float) $ratingSummary->rating_avg, 1)
            : 0.0;

        return view('mypage', [
            'user'               => $user,
            'page'               => $tab,
            'mySells'            => $mySells,
            'purchasedProducts'  => $purchasedProducts,
            'tradingProducts'    => $tradingProducts,
            'tradingUnreadTotal' => $tradingUnreadTotal,
            'ratingAverage'      => $ratingAverage,
            'ratingCount'        => $ratingCount,
        ]);
    }
}