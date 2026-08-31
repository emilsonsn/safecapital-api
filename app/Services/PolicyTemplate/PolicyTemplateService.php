<?php

namespace App\Services\PolicyTemplate;

use App\Models\PolicyTemplate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class PolicyTemplateService
{
    public const LEGACY_TEMPLATE_PATH = 'templates/policy_template.docx';

    public const REQUIRED_VARIABLES = [
        'contract_number',
        'date',
        'policy_value',
        'month_value',
        'address',
        'number',
        'complement',
        'cep',
        'property_type',
        'neighborhood',
        'city',
        'state',
        'name',
        'cpf',
        'email',
        'birthday',
        'phone',
        'corresponding_name',
        'corresponding_cpf',
        'corresponding_email',
        'corresponding_birthday',
        'corresponding_phone',
    ];

    public function current(): array
    {
        try {
            return [
                'status' => true,
                'data' => $this->configuration($this->currentModel()),
            ];
        } catch (Throwable $error) {
            return $this->errorResult($error);
        }
    }

    public function store(Request $request): array
    {
        $path = null;

        try {
            $validator = Validator::make($request->all(), [
                'document' => ['required', 'file', 'mimes:docx', 'max:20480'],
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 422);
            }

            $file = $request->file('document');
            $this->validateVariables($file->getRealPath());

            $path = $file->store('templates/policies', 'local');

            $template = DB::transaction(function () use ($file, $path) {
                $latestTemplate = PolicyTemplate::query()
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                $version = number_format(((float) ($latestTemplate?->version ?? 0)) + 1, 1, '.', '');

                return PolicyTemplate::create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'version' => $version,
                    'uploaded_by' => auth()->id(),
                ]);
            });

            return [
                'status' => true,
                'data' => $this->configuration($template),
            ];
        } catch (Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            return $this->errorResult($error);
        }
    }

    public function currentModel(): PolicyTemplate
    {
        $template = PolicyTemplate::query()->latest('id')->first();

        if (! $template) {
            throw new Exception('Template do contrato não encontrado', 404);
        }

        return $template;
    }

    public function currentPath(): string
    {
        $template = Schema::hasTable('policy_templates')
            ? PolicyTemplate::query()->latest('id')->first()
            : null;

        return $this->resolvePath($template?->path ?? self::LEGACY_TEMPLATE_PATH);
    }

    public function pathFor(PolicyTemplate $template): string
    {
        return $this->resolvePath($template->path);
    }

    private function validateVariables(string $path): void
    {
        try {
            $variables = (new TemplateProcessor($path))->getVariables();
        } catch (Throwable) {
            throw new Exception('O arquivo enviado não é um DOCX válido.', 422);
        }

        $missingVariables = array_values(array_diff(self::REQUIRED_VARIABLES, $variables));

        if ($missingVariables) {
            throw new Exception(
                'O template não contém os campos obrigatórios: '.implode(', ', $missingVariables),
                422
            );
        }
    }

    private function configuration(PolicyTemplate $template): array
    {
        return [
            'template' => $template,
            'required_variables' => self::REQUIRED_VARIABLES,
        ];
    }

    private function resolvePath(string $path): string
    {
        if (! Storage::disk('local')->exists($path)) {
            throw new Exception('Arquivo do template do contrato não encontrado', 404);
        }

        return Storage::disk('local')->path($path);
    }

    private function errorResult(Throwable $error): array
    {
        $statusCode = in_array($error->getCode(), [404, 422], true)
            ? $error->getCode()
            : 400;

        return [
            'status' => false,
            'error' => $error->getMessage(),
            'statusCode' => $statusCode,
        ];
    }
}
