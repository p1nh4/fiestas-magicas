<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Endereços antigos que continuam a responder.
 *
 * Em três formulários deste backoffice está escrito "mudar o endereço parte
 * as ligações que já circulam". Isto é o que faz com que deixe de ser
 * verdade: quando alguém muda um slug, o endereço antigo passa a
 * redirecionar para o novo, com 301.
 *
 * Interessa mais do que parece. Um link partilhado num grupo de WhatsApp
 * há seis meses vale um cliente; e para o Google, um 404 numa página
 * indexada é posição perdida, enquanto um 301 transfere-a para o endereço
 * novo.
 */
class Redirect extends Model
{
    use HasFactory;

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer'];
    }

    /**
     * Regista um endereço antigo, mantendo a cadeia com um salto só.
     *
     * Se um endereço mudar duas vezes (A → B → C), a solução ingénua deixa
     * /A a apontar para /B e /B para /C: dois saltos, que o Google conta
     * como perda de sinal e o navegador como duas viagens. Aqui, ao
     * registar B → C, tudo o que apontava para /B passa a apontar
     * diretamente para /C.
     */
    public static function remember(string $from, string $to, int $status = 301): ?self
    {
        $from = '/'.ltrim($from, '/');
        $to = '/'.ltrim($to, '/');

        // Um redirecionamento para si próprio é um ciclo infinito.
        if ($from === $to) {
            return null;
        }

        // O endereço novo pode ser um que já esteve em uso e foi
        // redirecionado. Se voltou a estar vivo, o redirecionamento antigo
        // deixa de fazer sentido e tem de sair da frente.
        static::query()->where('from_path', $to)->delete();

        // Encurtar a cadeia: quem apontava para o endereço antigo passa a
        // apontar direto para o novo.
        static::query()->where('to_path', $from)->update(['to_path' => $to]);

        return static::updateOrCreate(
            ['from_path' => $from],
            ['to_path' => $to, 'status_code' => $status],
        );
    }

    /** Onde ir, ou null se este endereço não está registado. */
    public static function resolve(string $path): ?self
    {
        return static::query()->where('from_path', '/'.ltrim($path, '/'))->first();
    }
}
