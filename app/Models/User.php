<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale;
use App\Models\Concerns\HasPublicUuid;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/** Equipa interna. Os clientes finais estao em App\Models\Client. */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use HasPublicUuid;
    use HasRoles;
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'locale', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locale' => Locale::class,
        ];
    }

    /** Uma conta desativada deixa de entrar no backoffice, sem apagar historico. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }
}
