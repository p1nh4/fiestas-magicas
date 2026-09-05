/*
 * Service worker.
 *
 * Estratégia deliberadamente conservadora: só guarda em cache o que é
 * estático (CSS, JS, imagens, fontes) e nunca guarda HTML nem respostas de
 * POST. Um service worker demasiado esperto num site com formulário é a
 * receita para alguém ver uma página antiga e voltar a submeter um pedido
 * que já tinha feito.
 */

const CACHE = 'decorarte-v1';

const PRECACHE = ['/offline.html'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    const isAsset = /\.(css|js|woff2?|png|jpe?g|webp|svg|ico)$/i.test(url.pathname);

    if (isAsset) {
        // Cache primeiro: os ficheiros do build têm hash no nome, por isso
        // uma versão nova nunca reutiliza a antiga.
        event.respondWith(
            caches.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
            )
        );
        return;
    }

    // HTML: rede sempre. Só se não houver rede é que mostra a página offline.
    event.respondWith(
        fetch(request).catch(() => caches.match('/offline.html'))
    );
});
