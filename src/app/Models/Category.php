<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    // 必要なカラムだけを fillable に
    protected $fillable = ['name', 'slug']; // slugを使わないなら ['name'] にしてOK

    /** products との多対多 */
    public function products(): BelongsToMany
    {
        // 既定ピボット: category_product (category_id, product_id)
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    /**
     * （任意）sells との多対多
     * ※ 本当に 'category_sell' ピボットがある場合のみ使う。無ければこのメソッドは削除してください。
     */
    public function sells(): BelongsToMany
    {
        return $this->belongsToMany(Sell::class, 'category_sell')->withTimestamps();
    }
}
