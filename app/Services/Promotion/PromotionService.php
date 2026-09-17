<?php

namespace App\Services\Promotion;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PromotionService
{
    public function listForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Promotion::query()->orderBy('order')->paginate($perPage);
    }

    public function listActive(): Collection
    {
        return Promotion::query()
            ->where('active', true)
            ->orderBy('order')
            ->get();
    }

    public function create(array $data, ?UploadedFile $image, User $admin): Promotion
    {
        $this->assertHasImage($image !== null);

        $nextOrder = (int) Promotion::max('order') + 1;

        return Promotion::create([
            'title' => $data['title'] ?? null,
            'text' => $data['text'] ?? null,
            'active' => $data['active'] ?? true,
            'order' => $nextOrder,
            'image_path' => $image->store('promotions', 'public'),
            'created_by' => $admin->id,
        ]);
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                Promotion::whereKey($id)->update(['order' => $index + 1]);
            }
        });
    }

    public function update(Promotion $promotion, array $data, ?UploadedFile $image): Promotion
    {
        $title = array_key_exists('title', $data) ? $data['title'] : $promotion->title;
        $text = array_key_exists('text', $data) ? $data['text'] : $promotion->text;

        $this->assertHasImage($image !== null || (bool) $promotion->image_path);

        if ($image) {
            if ($promotion->image_path) {
                Storage::disk('public')->delete($promotion->image_path);
            }
            $promotion->image_path = $image->store('promotions', 'public');
        }

        $promotion->title = $title;
        $promotion->text = $text;
        if (array_key_exists('active', $data)) {
            $promotion->active = $data['active'];
        }
        $promotion->save();

        return $promotion->fresh();
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
    }

    private function assertHasImage(bool $hasImage): void
    {
        if (! $hasImage) {
            throw ValidationException::withMessages([
                'image' => 'A imagem da promoção é obrigatória.',
            ]);
        }
    }
}
