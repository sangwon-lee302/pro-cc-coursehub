<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function courses(): BelongsToMany
    {
        // TODO: 第二引数 'course_tag' は Laravel の命名規約（course, tag のアルファベット順連結）と
        // 一致しており省略可能。削除する場合は database/migrations の course_tag テーブルとの対応を確認のこと。
        return $this->belongsToMany(Course::class, 'course_tag');
    }
}
