<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $primaryKey = 'entry_id';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'debit_account_id',
        'credit_account_id',
        'amount',
    ];
}
