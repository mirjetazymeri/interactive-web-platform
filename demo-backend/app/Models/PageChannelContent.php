<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageChannelContent extends Model
{
    protected $fillable = [
        'page_id',
        'channel_id',
        'title',
        'content',
        'meta_title',
        'meta_description',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function resolveContent(int $channelId): array
    {
        $override = $this->channelContents
            ->firstWhere('channel_id', $channelId);

        return [
            'title' => $override->title ?? $this->title,
            'content' => $override->content ?? $this->content,
            'meta_title' => $override->meta_title ?? $this->meta_title,
            'meta_description' => $override->meta_description ?? $this->meta_description,
        ];
    }

}
