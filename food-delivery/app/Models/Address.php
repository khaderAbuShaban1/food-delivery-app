<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'city',
        'street',
        'details',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Single line for order snapshot / driver display (matches app “full address”). */
    public function formattedDeliveryLine(): string
    {
        $city = trim((string) $this->city);
        $street = trim((string) $this->street);
        $details = $this->details !== null ? trim((string) $this->details) : '';

        if ($details !== '') {
            return "{$city} - {$street} - {$details}";
        }

        return "{$city} - {$street}";
    }
}
