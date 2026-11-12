<?php

namespace App\Http\Requests\Admin\Page;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return array_replace([
            'title'  => ['required','string','max:255'],
            'slug'   => ['nullable','string','max:255'],
            'content'=> ['nullable','string'],
            'layout' => ['nullable','string','max:100'],
            'status' => ['required', Rule::in(['draft','published','archived'])],

            'meta_title'       => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string'],
            'published_at'     => ['nullable','date'],

            // ✅ hierarchy safety
            'parent_id' => [
                'nullable',
                'integer',
                'exists:pages,id',
                Rule::notIn([$id]), // prevent self-parent
            ],

            'position' => ['nullable','integer','min:0'],

            // navigation
            'show_in_header'  => ['nullable','boolean'],
            'header_position' => ['nullable','integer'],
            'show_in_footer'  => ['nullable','boolean'],
            'footer_position' => ['nullable','integer'],
            'footer_section'  => ['nullable','string','max:100'],
            'nav_label'       => ['nullable','string','max:255'],
            'nav_new_tab'     => ['nullable','boolean'],

            // ✅ channels
            'channels' => ['nullable','array'],
            'channels.*' => ['integer','exists:channels,id'],

            // 🔥 channel content overrides
            'channel_content' => ['nullable','array'],
            'channel_content.*.title' => ['nullable','string'],
            'channel_content.*.content' => ['nullable','string'],
            'channel_content.*.meta_title' => ['nullable','string'],
            'channel_content.*.meta_description' => ['nullable','string'],

            'translations' => ['nullable','array'],
            'translations.*.locale' => ['required_with:translations','string','max:10'],
            'translations.*.title' => ['nullable','string','max:255'],
            'translations.*.content' => ['nullable','string'],
            'translations.*.meta_title' => ['nullable','string','max:255'],
            'translations.*.meta_description' => ['nullable','string'],
            'translations.*.nav_label' => ['nullable','string','max:255'],
        ], PageSeoRules::rules());
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {

            // 🧠 prevent parent = descendant (extra safety)
            if (!$this->parent_id || !$this->route('id')) {
                return;
            }

            $page = \App\Models\Page::with('children')->find($this->route('id'));

            if (!$page) return;

            $descIds = $page->descendants()->pluck('id')->all();

            if (in_array((int)$this->parent_id, $descIds, true)) {
                $v->errors()->add('parent_id', 'Parent cannot be a descendant.');
            }
        });
    }
}
