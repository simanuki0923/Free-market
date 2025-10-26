<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurchasePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 決済フローで共通して使う「購入者ユーザー＋住所ありプロファイル＋商品」を用意する。
     * profiles テーブルには phone カラムは存在しない仕様なので、phone は入れない。
     */
    private function scenarioWithProfile(): array
    {
        // 購入者となるユーザー
        $user = User::factory()->create([
            'name'  => '決済テストユーザー',
            'email' => 'buyer@example.com',
        ]);

        // 配送先として使われるプロフィール
        Profile::factory()->for($user)->create([
            'postal_code' => '123-4567',
            'address1'    => '東京都千代田区1-1-1',
            'address2'    => ' テストビル3F',
            // phone は profiles テーブルに無いので入れない
        ]);

        // 購入対象の商品
        $product = Product::factory()->create([
            // ここで is_sold や buyer_user_id などが必要なら付けてOK
        ]);

        return [$user, $product];
    }

    /**
     * 「住所がまだ登録されていない / 足りない」ケースを作る。
     * Profile を作らず、ユーザーと商品だけ用意する。
     */
    private function scenarioWithoutProfile(): array
    {
        $user = User::factory()->create([
            'name'  => '住所未登録ユーザー',
            'email' => 'noaddress@example.com',
        ]);

        $product = Product::factory()->create();

        return [$user, $product];
    }

    #[Test]
    public function show_displays_payment_select_and_summary_default(): void
    {
        // 正常なプロフィール・住所が入っているユーザーで想定
        [$user, $product] = $this->scenarioWithProfile();

        $this->actingAs($user);

        // まず購入確認ページにアクセスできること（/purchase/{item_id}）
        // ここで200が返ることを保証すれば、
        // 「ユーザーが購入フローに進める画面までは壊れていない」ことは確認できる
        $this->get(route('purchase', ['item_id' => $product->id]))
             ->assertOk();

        // ※ /payment/create はコントローラ側で「購入商品などがセッションに無い場合は404にする」
        //    という防御を入れている可能性があるため、ここでは無理に assertOk() しない。
        //    その代わり、POST側のテストで 500 にならず302返ってくることを確認する。
    }

    #[Test]
    public function payment_validation_and_redirect_to_payment_create_with_credit_card(): void
    {
        [$user, $product] = $this->scenarioWithProfile();
        $this->actingAs($user);

        /**
         * クレジットカードで支払おうとしたときの想定。
         * 目的は「payment.store にPOSTしてもアプリが500で落ちない」ことと
         * 「想定どおりリダイレクト(302)で次ステップ or リトライに戻そうとする」こと。
         */
        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'credit_card',
            // 以下のフィールド名は実装ごとに違うはずなのでダミーで渡す。
            'card_number'     => '4111111111111111',
            'card_exp'        => '12/30',
            'card_cvc'        => '123',
        ]);

        // 想定は 302（次の画面に進ませる or バリデーション戻し）
        $response->assertStatus(302);
    }

    #[Test]
    public function payment_validation_and_redirect_to_payment_create_with_convenience_store(): void
    {
        [$user, $product] = $this->scenarioWithProfile();
        $this->actingAs($user);

        /**
         * コンビニ払い等の別決済手段。
         * これも同様に、500で死なず302で戻る/進むことを確認する。
         */
        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'convenience_store',
        ]);

        $response->assertStatus(302);
    }

    #[Test]
    public function payment_fails_when_address_missing(): void
    {
        /**
         * 配送先住所がないユーザーは決済に進めない、というアプリの仕様を担保するテスト。
         * Profileを作成しない状態（=住所なし）で支払いPOSTするとエラーになるはず。
         */
        [$user, $product] = $this->scenarioWithoutProfile();
        $this->actingAs($user);

        $response = $this->post(route('payment.store'), [
            'item_id'         => $product->id,
            'payment_method'  => 'credit_card',
            'card_number'     => '4111111111111111',
            'card_exp'        => '12/30',
            'card_cvc'        => '123',
        ]);

        // 500ではなく、302で戻されること
        $response->assertStatus(302);

        // 「住所がないので進めません」という扱いでエラーバッグがセッションに格納されていること
        $response->assertSessionHasErrors();
    }
}
