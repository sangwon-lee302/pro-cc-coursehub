<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugGenerator
{
    /**
     * 名前から一意なスラッグを生成する
     *
     * 非ASCII文字のみの名前は Str::slug() が空文字列を返すため、
     * その場合はフォールバック文字列を使う。生成したスラッグが
     * 既存レコードと衝突する場合は連番を付与する。
     */
    public static function unique(string $table, string $name, string $fallbackPrefix, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            $slug = $fallbackPrefix.'-'.time();
        }

        $originalSlug = $slug;
        $count = 1;

        while (
            DB::table($table)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
