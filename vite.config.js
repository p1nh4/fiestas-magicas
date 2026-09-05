import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,

            // Bunny Fonts, não Google Fonts. É um espelho do catálogo do
            // Google sem cookies nem registo de IPs — e um tribunal alemão
            // já decidiu que incorporar o Google Fonts diretamente viola o
            // RGPD. Como os clientes são de Espanha e Portugal, isto importa.
            // O plugin descarrega os ficheiros no build; em produção as
            // fontes são servidas pelo nosso próprio domínio.
            fonts: [
                // texto corrido
                bunny('Figtree', { weights: [400, 500, 600, 700] }),
                // títulos
                bunny('Marcellus', { weights: [400] }),
                // só o logótipo — uma letra manuscrita não se usa em mais nada
                bunny('Pinyon Script', { weights: [400] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
