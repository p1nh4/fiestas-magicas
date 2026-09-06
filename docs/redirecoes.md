# Endereços antigos

## O problema

Em três formulários do backoffice está escrito, por baixo do campo do
endereço: *"cambiarla rompe los enlaces que ya circulan"*. Era verdade, e um
aviso que não se pode cumprir é um aviso inútil — quem muda o endereço não o
lê, e quem apanha o 404 não avisa ninguém.

Um link partilhado num grupo de WhatsApp há seis meses vale um cliente. Para
o Google, um 404 numa página indexada é posição perdida; um 301 transfere-a
para o endereço novo.

## O que passa a acontecer

Ao mudar o endereço de uma zona, de uma peça de aluguer ou de uma página de
texto, o endereço antigo fica registado a apontar para o novo, com 301. Não
é preciso fazer nada — acontece ao gravar.

Vê-se em **Web → Redirecciones**, ordenado por quantas pessoas ainda chegam
por lá. Um número alto meses depois quer dizer que o link antigo continua a
circular algures.

## Registar um à mão

Serve para links de cartazes, de anúncios ou de um QR code impresso:

    /promo-comuniones  →  /es/zonas/nigran

O 301 diz "isto mudou de casa para sempre" e é o que transfere a posição no
Google. O 302 diz "isto volta" — usa-o só se for mesmo temporário, senão o
Google mantém a página antiga indexada.

## A cadeia

Se um endereço mudar duas vezes (A → B → C), o que fica registado é A → C e
B → C, e não A → B → C. Dois saltos custam uma viagem a mais ao navegador e
sinal perdido a cada salto para o Google.

E se voltares atrás — de B para A — o redirecionamento que apontava para
fora de A desaparece sozinho, porque A está vivo outra vez.

## O custo

Nenhum, nas visitas normais. A consulta à base de dados só acontece quando a
resposta ia ser 404, e essas são poucas.
