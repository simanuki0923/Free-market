@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<main class="product-list">
    <div class="container">

        {{-- タブ --}}
        <nav class="tab-buttons">
            <a href="#" id="toggleAll" class="tab-link active">おすすめ</a>
            @auth
                <a href="#" id="toggleMyList" class="tab-link">マイリスト</a>
            @endauth
        </nav>

        @php
            // ルート制限（「mypageのみ」）に対応：productルートが無ければリンクを無効化
            $hasProductRoute = \Illuminate\Support\Facades\Route::has('product');
        @endphp

        {{-- 1) 商品一覧（未認証ユーザーにも表示） --}}
        <section id="allProducts" class="product-list__section" style="display:block;">
            <ul class="product-list__items">
                @forelse ($products as $product)
                    @auth
                        {{-- 念のためビュー側でも自己出品を除外（コントローラでも除外済み） --}}
                        @continue($product->user_id === auth()->id())
                    @endauth
                    <li class="product-item">
                        <a href="{{ $hasProductRoute ? route('product', ['product' => $product->id]) : 'javascript:void(0);' }}"
                           class="product-card">
                            <figure class="product-thumb">
                                <img
                                    src="{{ $product->image_url ? asset('storage/' . $product->image_url) : asset('storage/img/no-image.png') }}"
                                    alt="{{ $product->name }}">
                                @if ($product->is_sold)
                                    <span class="sold-out-label" aria-label="売り切れ">Sold</span>
                                @endif
                            </figure>
                            <div class="product-meta">
                                <p class="product-name">{{ $product->name }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="product-empty">商品がありません。</li>
                @endforelse
            </ul>
        </section>

        {{-- 2) マイリスト（認証時のみ表示／未認証は何も表示しない） --}}
        @auth
        <section id="myList" class="product-list__section" style="display:none;">
            <ul class="product-list__items">
                @forelse ($myListProducts as $product)
                    <li class="product-item">
                        <a href="{{ $hasProductRoute ? route('product', ['product' => $product->id]) : 'javascript:void(0);' }}"
                           class="product-card">
                            <figure class="product-thumb">
                                <img
                                    src="{{ $product->image_url ? asset('storage/' . $product->image_url) : asset('storage/img/no-image.png') }}"
                                    alt="{{ $product->name }}">
                                @if ($product->is_sold)
                                    <span class="sold-out-label" aria-label="売り切れ">Sold</span>
                                @endif
                            </figure>
                            <div class="product-meta">
                                <p class="product-name">{{ $product->name }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    {{-- 認証済でも空なら何も出さない運用にする場合はこの行を削除 --}}
                    <li class="product-empty">マイリストに商品がありません。</li>
                @endforelse
            </ul>
        </section>
        @endauth

    </div>
</main>

{{-- タブ切替（未認証時はマイリストタブ自体が無い） --}}
<script>
(function() {
    const allTab = document.getElementById('toggleAll');
    const myTab  = document.getElementById('toggleMyList');
    const allSec = document.getElementById('allProducts');
    const mySec  = document.getElementById('myList');

    if (allTab) {
        allTab.addEventListener('click', function(e) {
            e.preventDefault();
            allSec.style.display = 'block';
            if (mySec) mySec.style.display = 'none';
            allTab.classList.add('active');
            if (myTab) myTab.classList.remove('active');
        });
    }

    if (myTab && mySec) {
        myTab.addEventListener('click', function(e) {
            e.preventDefault();
            allSec.style.display = 'none';
            mySec.style.display  = 'block';
            myTab.classList.add('active');
            allTab.classList.remove('active');
        });
    }
})();
</script>
@endsection
