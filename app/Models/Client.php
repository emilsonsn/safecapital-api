<?php

namespace App\Models;

use App\Enums\PropertyTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    public $table = 'clients';

    // criar metodo cast
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'surname' => 'string',
        'birthday' => 'date',
        'email' => 'string',
        'phone' => 'string',
        'cpf' => 'string',
        'cep' => 'string',
        'street' => 'string',
        'number' => 'string',
        'property_type' => PropertyTypeEnum::class,
        'rental_value' => 'float',
        'property_tax' => 'float',
        'condominium_fee' => 'float',
        'policy_value' => 'float',
        'neighborhood' => 'string',
        'observations' => 'string',
        'payment_form' => 'string',
        'complement' => 'string',
        'city' => 'string',
        'state' => 'string',        
    ];

    protected $fillable = [
        'name',
        'surname',
        'birthday',
        'email',
        'phone',
        'cpf',
        'cep',
        'street',
        'number',
        'property_type',
        'doc4sign_document_uuid',
        'contract_number',
        'rental_value',
        'property_tax',
        'condominium_fee',
        'policy_value',
        'neighborhood',
        'observations',
        'payment_form',
        'complement',
        'city',
        'state',
        'status',
        'user_id',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(related: User::class);
    }

    public function attachments(): HasMany{
        return $this->hasMany(ClientAttachment::class);
    }

    public function policys(): HasMany{
        return $this->hasMany(PolicyDocument::class);
    }

    public function analisys(): HasOne{
        return $this->hasOne(ClientPh3Analisy::class);
    }

    public function corresponding(): HasOne{
        return $this->hasOne(related: Corresponding::class);
    }
}