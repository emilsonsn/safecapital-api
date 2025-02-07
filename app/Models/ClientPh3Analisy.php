<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPh3Analisy extends Model
{
    use HasFactory;

    public $table = 'client_ph3_analisys';

    protected $fillable = [
        'client_id',
        'response',
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }
}
