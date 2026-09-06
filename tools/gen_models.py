"""Escreve os models Eloquent. Um ficheiro por model, em build/app/Models."""
import pathlib, textwrap

OUT = pathlib.Path('build/app/Models')
OUT.mkdir(parents=True, exist_ok=True)
for old in OUT.glob('*.php'):
    old.unlink()
(OUT / 'Concerns').mkdir(exist_ok=True)

HEAD = "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Models;\n\n"

FILES: dict[str, str] = {}

# ---------------------------------------------------------------- concern
FILES['Concerns/HasPublicUuid.php'] = '''<?php

declare(strict_types=1);

namespace App\\Models\\Concerns;

/**
 * Identificador publico separado da chave interna.
 *
 * O id sequencial fica em casa; nas URLs e nas APIs vai o uuid. Assim
 * ninguem consegue contar quantos clientes ou quantas festas ha so por
 * olhar para um link (enumeration attack), e a chave interna continua a
 * ser um bigint, que e o que indexa bem.
 */
trait HasPublicUuid
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
'''

# ---------------------------------------------------------------- User
FILES['User.php'] = HEAD + '''use App\\Enums\\Locale;
use App\\Models\\Concerns\\HasPublicUuid;
use Filament\\Models\\Contracts\\FilamentUser;
use Filament\\Panel;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Spatie\\Permission\\Traits\\HasRoles;

/** Equipa interna. Os clientes finais estao em App\\Models\\Client. */
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
'''

# ---------------------------------------------------------------- Client
FILES['Client.php'] = HEAD + '''use App\\Enums\\ClientKind;
use App\\Enums\\Locale;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class Client extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'kind', 'name', 'legal_name', 'tax_id', 'email', 'phone', 'whatsapp',
        'locale', 'address_line', 'postal_code', 'city', 'province', 'country',
        'source', 'notes', 'marketing_opt_in_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ClientKind::class,
            'locale' => Locale::class,
            'marketing_opt_in_at' => 'immutable_datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** So se pode enviar marketing a quem deu consentimento explicito (RGPD). */
    public function acceptsMarketing(): bool
    {
        return $this->marketing_opt_in_at !== null;
    }
}
'''

# ---------------------------------------------------------------- Category
FILES['Category.php'] = HEAD + '''use App\\Enums\\CategoryKind;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Spatie\\Translatable\\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'slug'];

    protected $fillable = ['kind', 'name', 'slug', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'kind' => CategoryKind::class,
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
'''

# ---------------------------------------------------------------- Service
FILES['Service.php'] = HEAD + '''use App\\Enums\\PriceMode;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Spatie\\Translatable\\HasTranslations;

class Service extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;

    public array $translatable = ['name', 'slug', 'summary', 'description'];

    protected $fillable = [
        'category_id', 'name', 'slug', 'summary', 'description',
        'base_price', 'price_mode', 'setup_minutes', 'is_active', 'position', 'seo',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price_mode' => PriceMode::class,
            'setup_minutes' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
            'seo' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
'''

# ---------------------------------------------------------------- Item
FILES['Item.php'] = HEAD + '''use App\\Models\\Concerns\\HasPublicUuid;
use App\\Models\\Concerns\\KeepsOldUrls;
use App\\Support\\Period;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Spatie\\Translatable\\HasTranslations;

/**
 * Peca fisica com stock. E aqui que vive o risco de overbooking — ver
 * App\\Support\\Availability\\AvailabilityService e o trigger
 * reservations_check_capacity() no schema.
 */
class Item extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use KeepsOldUrls;

    public array $translatable = ['name', 'slug', 'description'];

    /** Tem pagina publica em /{idioma}/alquiler/{slug}. */
    public function publicRouteName(): ?string
    {
        return 'rentals.show';
    }

    protected $fillable = [
        'category_id', 'sku', 'name', 'slug', 'description',
        'stock_qty', 'price_per_day', 'replacement_value',
        'buffer_before_min', 'buffer_after_min',
        'requires_transport', 'is_rentable', 'is_active',
    ];

    /*
     * Os mesmos valores por omissao do schema.sql.
     *
     * O Eloquent NAO conhece os DEFAULT da base de dados. Um
     * `Item::create([...])` sem folgas fica com null em memoria — a linha
     * gravada leva os 120/1440 do Postgres, mas o objeto que fica na mao
     * nao. E entao `blockedWindowFor()` chama `padded(null, null)` e
     * rebenta com um TypeError.
     *
     * Foi o mesmo erro que apanhou o `Lead::status`. Repetir os defaults
     * aqui e duplicacao, sim — mas e duplicacao que o gerador mantem
     * sincronizada com o schema, e a alternativa e codigo defensivo
     * espalhado por todo o lado a perguntar se o valor existe.
     */
    protected $attributes = [
        'stock_qty' => 1,
        'price_per_day' => 0,
        'buffer_before_min' => 120,
        'buffer_after_min' => 1440,
        'requires_transport' => false,
        'is_rentable' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'integer',
            'price_per_day' => 'decimal:2',
            'replacement_value' => 'decimal:2',
            'buffer_before_min' => 'integer',
            'buffer_after_min' => 'integer',
            'requires_transport' => 'boolean',
            'is_rentable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** Janela realmente bloqueada: o uso mais as folgas de logistica. */
    public function blockedWindowFor(Period $usage): Period
    {
        return $usage->padded($this->buffer_before_min, $this->buffer_after_min);
    }

    /** Catalogo publico de aluguer: so o que esta ativo e e alugavel a solto. */
    public function scopeRentable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_rentable', true);
    }
}
'''

# ---------------------------------------------------------------- Lead
FILES['Lead.php'] = HEAD + '''use App\\Enums\\EventType;
use App\\Enums\\LeadStatus;
use App\\Enums\\Locale;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

/** Pedido vindo do site. Ainda nao e cliente. */
class Lead extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'locale', 'event_type', 'event_date',
        'guests_count', 'venue', 'budget_hint', 'message', 'status',
        'utm_source', 'utm_medium', 'utm_campaign', 'referrer', 'landing_path',
        'ip_hash', 'user_agent', 'contacted_at', 'converted_at', 'lost_reason',
    ];

    /* Ver a nota no Item: o Eloquent nao conhece os DEFAULT do Postgres. */
    protected $attributes = [
        'status' => 'new',
        'locale' => 'es',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'event_type' => EventType::class,
            'locale' => Locale::class,
            'event_date' => 'immutable_date',
            'guests_count' => 'integer',
            'contacted_at' => 'immutable_datetime',
            'converted_at' => 'immutable_datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * O IP nunca fica em claro. Guarda-se um hash, que chega para detetar
     * spam vindo da mesma origem sem armazenar um dado pessoal (RGPD).
     */
    public static function hashIp(?string $ip): ?string
    {
        return $ip === null ? null : hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
'''

# ---------------------------------------------------------------- Event
FILES['Event.php'] = HEAD + '''use App\\Enums\\EventStatus;
use App\\Enums\\EventType;
use App\\Enums\\Locale;
use App\\Models\\Concerns\\HasPublicUuid;
use App\\Support\\Period;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class Event extends Model
{
    use HasFactory;
    use HasPublicUuid;
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'lead_id', 'reference', 'title', 'event_type', 'status',
        'starts_at', 'ends_at', 'setup_starts_at', 'teardown_ends_at',
        'venue_name', 'venue_address', 'venue_city', 'distance_km', 'guests_count',
        'locale', 'notes', 'internal_notes',
        'total_amount', 'deposit_amount', 'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'event_type' => EventType::class,
            'locale' => Locale::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'setup_starts_at' => 'immutable_datetime',
            'teardown_ends_at' => 'immutable_datetime',
            'distance_km' => 'decimal:1',
            'guests_count' => 'integer',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** Orcamento em vigor: a versao mais alta. */
    public function currentQuote(): ?Quote
    {
        return $this->quotes()->orderByDesc('version')->first();
    }

    /**
     * Janela de ocupacao da equipa: da montagem ao levantamento.
     * Quando nao ha horas de montagem definidas, usa-se o evento em si.
     */
    public function occupancyWindow(): Period
    {
        return new Period(
            $this->setup_starts_at ?? $this->starts_at,
            $this->teardown_ends_at ?? $this->ends_at,
        );
    }

    public function balanceDue(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->paid_amount, 2);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }
}
'''

# ---------------------------------------------------------------- Quote
FILES['Quote.php'] = HEAD + '''use App\\Enums\\QuoteStatus;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

/**
 * Orcamento. Nunca se edita um que ja foi enviado — cria-se a versao
 * seguinte. Assim ha sempre prova do que o cliente viu quando aceitou.
 */
class Quote extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'event_id', 'version', 'status', 'public_token', 'valid_until', 'currency',
        'subtotal', 'discount_amount', 'tax_rate', 'tax_amount', 'total',
        'deposit_pct', 'notes', 'sent_at', 'viewed_at', 'accepted_at',
        'accepted_ip_hash', 'rejected_at',
    ];

    protected $hidden = ['public_token', 'accepted_ip_hash'];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'version' => 'integer',
            'valid_until' => 'immutable_date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'deposit_pct' => 'decimal:2',
            'notes' => 'array',
            'sent_at' => 'immutable_datetime',
            'viewed_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'rejected_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('position');
    }

    public function isEditable(): bool
    {
        return $this->status === QuoteStatus::Draft;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    public function depositAmount(): string
    {
        return bcdiv(bcmul((string) $this->total, (string) $this->deposit_pct, 4), '100', 2);
    }
}
'''

# ---------------------------------------------------------------- QuoteLine
FILES['QuoteLine.php'] = HEAD + '''use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

/**
 * A descricao e o preco ficam congelados na linha. Se o preco do catalogo
 * mudar amanha, o orcamento de hoje continua a dizer o que dizia.
 */
class QuoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'service_id', 'item_id', 'description',
        'quantity', 'days', 'unit_price', 'line_total', 'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'days' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** bcmath e nao float: dinheiro nao se soma em virgula flutuante. */
    public function computeTotal(): string
    {
        return bcmul(
            bcmul((string) $this->quantity, (string) $this->unit_price, 4),
            (string) max(1, (int) $this->days),
            2
        );
    }
}
'''

# ---------------------------------------------------------------- Reservation
FILES['Reservation.php'] = HEAD + '''use App\\Casts\\PeriodCast;
use App\\Enums\\ReservationStatus;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

/**
 * Bloqueio de uma peca durante um intervalo.
 *
 * O `period` ja inclui as folgas de montagem e limpeza da peca. Sem evento
 * associado, e um bloqueio manual (manutencao, peca partida) e o
 * `blocked_reason` e obrigatorio.
 *
 * Escrever aqui pode rebentar: o trigger da base de dados recusa a linha
 * se nao houver stock. Usa o AvailabilityService, que traduz esse erro.
 */
class Reservation extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'item_id', 'event_id', 'quantity', 'period',
        'status', 'blocked_reason', 'hold_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => PeriodCast::class,
            'status' => ReservationStatus::class,
            'quantity' => 'integer',
            'hold_expires_at' => 'immutable_datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isMaintenanceBlock(): bool
    {
        return $this->event_id === null;
    }

    /** So o que conta para o stock. Reservas canceladas nao ocupam nada. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '<>', ReservationStatus::Cancelled->value);
    }

    /** Reservas que se sobrepoem a um intervalo, no lado do Postgres. */
    public function scopeOverlapping(Builder $query, string $range): Builder
    {
        return $query->whereRaw('period && ?::tstzrange', [$range]);
    }
}
'''

# ---------------------------------------------------------------- Payment
FILES['Payment.php'] = HEAD + '''use App\\Enums\\PaymentKind;
use App\\Enums\\PaymentMethod;
use App\\Enums\\PaymentStatus;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'event_id', 'client_id', 'kind', 'method', 'status', 'amount', 'currency',
        'provider', 'provider_reference', 'paid_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'meta' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Paid->value);
    }
}
'''

# ---------------------------------------------------------------- DocumentSeries
FILES['DocumentSeries.php'] = HEAD + '''use App\\Enums\\DocumentType;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

/**
 * Serie de numeracao. Separada por tipo e por ano — e o que a lei
 * espanhola pede a quem fatura.
 */
class DocumentSeries extends Model
{
    use HasFactory;

    protected $table = 'document_series';

    protected $fillable = ['code', 'doc_type', 'prefix', 'year', 'next_sequence', 'is_active'];

    protected function casts(): array
    {
        return [
            'doc_type' => DocumentType::class,
            'year' => 'integer',
            'next_sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'series_id');
    }

    public function formatNumber(int $sequence): string
    {
        return sprintf('%s/%d/%04d', $this->prefix ?: $this->code, $this->year, $sequence);
    }
}
'''

# ---------------------------------------------------------------- Document
FILES['Document.php'] = HEAD + '''use App\\Enums\\DocumentType;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use RuntimeException;

/**
 * Documento emitido.
 *
 * Depois de selado (`locked_at`), a BASE DE DADOS recusa qualquer UPDATE
 * ou DELETE — ver o trigger documents_immutability(). Os campos
 * content_hash / previous_hash formam a cadeia exigida pelo Veri*factu,
 * obrigatorio para autonomos a partir de 1 de julho de 2027.
 */
class Document extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'series_id', 'sequence', 'number', 'doc_type', 'event_id', 'client_id',
        'quote_id', 'issued_at', 'currency', 'subtotal', 'tax_rate', 'tax_amount',
        'total', 'snapshot', 'pdf_path', 'chain_index', 'content_hash',
        'previous_hash', 'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'doc_type' => DocumentType::class,
            'issued_at' => 'immutable_datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'snapshot' => 'array',
            'sequence' => 'integer',
            'chain_index' => 'integer',
            'locked_at' => 'immutable_datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(DocumentSeries::class, 'series_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isSealed(): bool
    {
        return $this->locked_at !== null;
    }

    /**
     * Impressao digital do conteudo, encadeada com a do documento anterior
     * da mesma serie. Alterar um documento antigo passa a ser detetavel:
     * a cadeia deixa de fechar.
     */
    public function computeHash(?string $previousHash): string
    {
        return hash('sha256', implode('|', [
            $this->number,
            $this->doc_type instanceof DocumentType ? $this->doc_type->value : (string) $this->doc_type,
            $this->issued_at?->toIso8601String() ?? '',
            (string) $this->total,
            (string) $this->tax_amount,
            json_encode($this->snapshot ?? [], JSON_THROW_ON_ERROR),
            $previousHash ?? '',
        ]));
    }

    protected static function booted(): void
    {
        // rede a mais, de proposito: a base de dados ja recusa, mas assim o
        // erro aparece no PHP com uma mensagem que se percebe
        static::updating(function (self $doc): void {
            if ($doc->getOriginal('locked_at') !== null) {
                throw new RuntimeException(
                    "O documento {$doc->number} está selado e não pode ser alterado."
                );
            }
        });

        static::deleting(function (self $doc): void {
            if ($doc->locked_at !== null) {
                throw new RuntimeException(
                    "O documento {$doc->number} está selado e não pode ser apagado."
                );
            }
        });
    }
}
'''

# ---------------------------------------------------------------- Project
FILES['Project.php'] = HEAD + '''use App\\Enums\\EventType;
use App\\Models\\Concerns\\HasPublicUuid;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Spatie\\MediaLibrary\\HasMedia;
use Spatie\\MediaLibrary\\InteractsWithMedia;
use Spatie\\MediaLibrary\\MediaCollections\\Models\\Media;
use Spatie\\Translatable\\HasTranslations;

/**
 * Trabalho publicado no portefolio. E a principal arma de SEO do site.
 *
 * `consent_at` nao e burocracia: sao fotos da festa de outra pessoa. A
 * base de dados tem um CHECK que impede publicar sem autorizacao.
 */
class Project extends Model implements HasMedia
{
    use HasFactory;
    use HasPublicUuid;
    use HasTranslations;
    use InteractsWithMedia;

    public array $translatable = ['title', 'slug', 'description'];

    protected $fillable = [
        'event_id', 'title', 'slug', 'description', 'event_type', 'happened_on',
        'venue', 'city', 'guests_count', 'is_featured', 'is_published',
        'published_at', 'position', 'seo', 'consent_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'happened_on' => 'immutable_date',
            'guests_count' => 'integer',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'immutable_datetime',
            'position' => 'integer',
            'seo' => 'array',
            'consent_at' => 'immutable_datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(480)->format('webp')->nonQueued();
        $this->addMediaConversion('card')->width(960)->format('webp');
        $this->addMediaConversion('full')->width(1920)->format('webp');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function canBePublished(): bool
    {
        return $this->consent_at !== null;
    }
}
'''

# ---------------------------------------------------------------- conteudo simples
FILES['Page.php'] = HEAD + '''use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Spatie\\Translatable\\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'slug', 'body'];

    protected $fillable = ['key', 'title', 'slug', 'body', 'seo', 'is_published'];

    protected function casts(): array
    {
        return ['seo' => 'array', 'is_published' => 'boolean'];
    }
}
'''

FILES['Faq.php'] = HEAD + '''use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Spatie\\Translatable\\HasTranslations;

class Faq extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'position', 'is_published'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_published' => 'boolean'];
    }
}
'''

FILES['Testimonial.php'] = HEAD + '''use App\\Enums\\Locale;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

/**
 * Testemunhos REAIS. `consent_at` e NOT NULL na base de dados: sem
 * autorizacao de quem escreveu, nao entra. Nunca preencher esta tabela
 * com texto inventado — nenhum site serio do setor tem testemunhos falsos.
 */
class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'event_id', 'author_name', 'body', 'locale',
        'rating', 'source', 'source_url', 'consent_at', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'rating' => 'integer',
            'consent_at' => 'immutable_datetime',
            'is_published' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNotNull('consent_at');
    }
}
'''

FILES['Redirect.php'] = HEAD + '''use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

/** Quando um slug muda, o SEO nao se perde. */
class Redirect extends Model
{
    use HasFactory;

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer'];
    }
}
'''

FILES['NewsletterSubscriber.php'] = HEAD + '''use App\\Enums\\Locale;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['email', 'locale', 'token', 'confirmed_at', 'unsubscribed_at', 'created_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'confirmed_at' => 'immutable_datetime',
            'unsubscribed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** Double opt-in: so quem confirmou e nao saiu recebe email. */
    public function scopeMailable(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
'''

for name, body in FILES.items():
    path = OUT / name
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(body, encoding='utf-8')
    print(f'  {name}')

print(f'\n{len(FILES)} ficheiros escritos')
