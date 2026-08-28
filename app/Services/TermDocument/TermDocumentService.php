<?php

namespace App\Services\TermDocument;

use App\Models\TermDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TermDocumentService
{
    public function current(): array
    {
        try {
            $term = TermDocument::query()->latest('id')->first();

            if (! $term) {
                throw new Exception('Termo de uso não encontrado', 404);
            }

            return ['status' => true, 'data' => $term];
        } catch (Exception $error) {
            return [
                'status' => false,
                'error' => $error->getMessage(),
                'statusCode' => $error->getCode() === 404 ? 404 : 400,
            ];
        }
    }

    public function store(Request $request): array
    {
        $path = null;

        try {
            $validator = Validator::make($request->all(), [
                'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 422);
            }

            $file = $request->file('document');
            $path = $file->store('term-documents', 'public');

            $term = DB::transaction(function () use ($file, $path) {
                $latestTerm = TermDocument::query()
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                $version = number_format(((float) ($latestTerm?->version ?? 0)) + 1, 1, '.', '');

                return TermDocument::create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'external_url' => null,
                    'version' => $version,
                    'uploaded_by' => auth()->id(),
                ]);
            });

            return ['status' => true, 'data' => $term];
        } catch (Exception $error) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            return [
                'status' => false,
                'error' => $error->getMessage(),
                'statusCode' => $error->getCode() === 422 ? 422 : 400,
            ];
        }
    }
}
