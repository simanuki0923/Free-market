@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<main class="contact-form__main">

    {{-- プロフィール（ユーザー情報） --}}
    <section class="profile-section">
        <div class="profile-icon">
            <img
                src="{{ $user->profile && $user->profile->icon_image_path ? asset('storage/' . $user->profile->icon_image_path) : asset('img/sample.jpg') }}"
                alt="{{ ($user_name ?? $user->name) . 'のアイコン' }}"
            >
        </div>
        <div class="profile-info">
            <div class="profile-info-content">
                <p class="user-name">{{ $user_name ?? $user->name }}</p>
            </div>
            <a href="{{ route('mypage.profile') }}" class="edit-profile-btn" aria-label="プロフィール編集へ">
                プロフィールを編集
            </a>
        </div>
    </section>

    {{-- タブ切り替え --}}
    <nav class="toggle-links" role="tablist" aria-label="出品/購入 切替">
        <a href="javascript:void(0);" id="toggleListed" class="active" role="tab" aria-selected="true" aria-controls="listedProducts">
            出品した商品
        </a>
        <a href="javascript:void(0);" id="togglePurchased" role="tab" aria-selected="false" aria-controls="purchasedProducts">
            購入した商品
        </a>
    </nav>

    {{-- 出品した商品一覧 --}}
    <section id="listedProducts" class="product-list" role="tabpanel" aria-labelledby="toggleListed">
        @if($listedProducts->isNotEmpty())
            @foreach ($listedProducts as $product)
                <article class="product-item">
                    <a href="{{ route('product', ['id' => $product->id]) }}" class="product-link" aria-label="{{ $product->name }}の詳細へ">
                        <img
                            src="{{ $product->image_url ? asset('storage/' . $product->image_url) : asset('storage/img/no-image.png') }}"
                            alt="{{ $product->name }}"
                        >
                        @if ($product->is_sold)
                            <span class="sold-out-label" aria-label="売り切れ">sold out</span>
                        @endif
                    </a>
                    <h3 class="product-name">{{ $product->name }}</h3>
                </article>
            @endforeach
        @else
            <p class="empty-notice">出品した商品はありません。</p>
        @endif
    </section>

    {{-- 購入した商品一覧 --}}
    <section id="purchasedProducts" class="product-list" role="tabpanel" aria-labelledby="togglePurchased" style="display:none;">
        @if($purchasedProducts->isNotEmpty())
            @foreach ($purchasedProducts as $product)
                @php
                    // オブジェクト/配列どちらでも対応（join結果などの配列対応用）
                    $pId   = $product->id   ?? $product['id']   ?? null;
                    $pName = $product->name ?? $product['name'] ?? '商品名';
                    $pImg  = $product->image_url ?? $product['image_url'] ?? null;
                    $pSold = $product->is_sold   ?? $product['is_sold']   ?? false;
                @endphp
                <article class="product-item">
                    <a href="{{ $pId ? route('product', ['id' => $pId]) : 'javascript:void(0);' }}" class="product-link" aria-label="{{ $pName }}の詳細へ">
                        <img
                            src="{{ $pImg ? asset('storage/' . $pImg) : asset('storage/img/no-image.png') }}"
                            alt="{{ $pName }}"
                        >
                        @if ($pSold)
                            <span class="sold-out-label" aria-label="売り切れ">sold</span>
                        @endif
                    </a>
                    <h3 class="product-name">{{ $pName }}</h3>
                </article>
            @endforeach
        @else
            <p class="empty-notice">購入した商品はありません。</p>
        @endif
    </section>
</main>

{{-- タブ切替用スクリプト --}}
<script>
    (function () {
        const $listedSection = document.getElementById('listedProducts');
        const $purchasedSection = document.getElementById('purchasedProducts');
        const $listedTab = document.getElementById('toggleListed');
        const $purchasedTab = document.getElementById('togglePurchased');

        function showSection(sectionToShow, sectionToHide, tabToActivate, tabToDeactivate) {
            sectionToShow.style.display = 'grid';
            sectionToHide.style.display = 'none';

            tabToActivate.classList.add('active');
            tabToDeactivate.classList.remove('active');

            tabToActivate.setAttribute('aria-selected', 'true');
            tabToDeactivate.setAttribute('aria-selected', 'false');
        }

        $listedTab.addEventListener('click', function () {
            showSection($listedSection, $purchasedSection, $listedTab, $purchasedTab);
        });

        $purchasedTab.addEventListener('click', function () {
            showSection($purchasedSection, $listedSection, $purchasedTab, $listedTab);
        });

        // 初期表示
        $listedSection.style.display = 'grid';
        $purchasedSection.style.display = 'none';
        $listedTab.setAttribute('aria-selected', 'true');
        $purchasedTab.setAttribute('aria-selected', 'false');
    })();
</script>
@endsection
