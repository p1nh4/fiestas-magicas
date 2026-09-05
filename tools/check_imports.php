<?php

declare(strict_types=1);

/*
| Verifica que todos os `use X;` de app/ existem mesmo, e que as constantes
| de enum escritas no código (Heroicon::OutlinedCube, EventStatus::Draft…)
| também existem.
|
| Porquê: um nome de classe errado num resource do Filament não dá erro
| nenhum até alguém abrir a página — e aí dá um ecrã branco. Isto apanha-o
| em dois segundos, sem base de dados e sem servidor.
|
|   php tools/check_imports.php
*/

require __DIR__.'/../vendor/autoload.php';

$roots = [__DIR__.'/../app'];
$problems = [];
$checkedClasses = 0;
$checkedConsts = 0;
$files = 0;

foreach ($roots as $root) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($it as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $files++;
        $src = file_get_contents($file->getPathname());
        $rel = str_replace(dirname(__DIR__).'/', '', $file->getPathname());

        // --- os imports ------------------------------------------------
        preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)\s*;/m', $src, $m);

        foreach ($m[1] as $class) {
            $checkedClasses++;

            if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class) && ! enum_exists($class)) {
                $problems[] = "{$rel}: use {$class}; — nao existe";
            }
        }

        // --- Classe::CONSTANTE, so para as classes importadas ----------
        $alias = [];
        foreach ($m[1] as $class) {
            $alias[substr((string) strrchr('\\'.$class, '\\'), 1)] = $class;
        }

        preg_match_all('/\b([A-Z][A-Za-z0-9_]*)::([A-Z][A-Za-z0-9_]*)\b/', $src, $c, PREG_SET_ORDER);

        foreach ($c as [$whole, $short, $const]) {
            if (! isset($alias[$short]) || $const === 'class') {
                continue;
            }

            $target = $alias[$short];

            if (! class_exists($target) && ! enum_exists($target)) {
                continue;   // ja reportado acima
            }

            $checkedConsts++;

            if (! defined("{$target}::{$const}")) {
                $problems[] = "{$rel}: {$short}::{$const} — nao existe em {$target}";
            }
        }
    }
}

echo "{$files} ficheiros, {$checkedClasses} imports, {$checkedConsts} constantes\n";

if ($problems !== []) {
    echo "\n".count($problems)." problemas:\n";
    foreach (array_unique($problems) as $p) {
        echo '  '.$p."\n";
    }
    exit(1);
}

echo "nenhum problema\n";
