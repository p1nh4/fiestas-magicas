# Falta uma linha em bootstrap/app.php

O middleware que segue os endereços antigos tem de ser registado à mão:

```php
->withMiddleware(function (Middleware $middleware) {
    // ... o que já lá estiver

    // Antes de devolver um 404, vê se aquele endereço já existiu.
    // Global e não só no grupo `web`: um link antigo pode não ter sequer
    // prefixo de idioma (/photocall-antiguo), e nesse caso nenhuma rota
    // chega a corresponder.
    $middleware->append(\App\Http\Middleware\FollowRedirects::class);
})
```

Sem esta linha, tudo o resto funciona — os redirecionamentos ficam
registados e aparecem no backoffice — mas ninguém é encaminhado. Os testes
`RedirectTest` apanham-no: três deles falham.
