<?php

// Prepara o Flarum de teste do performance.yml: liga as duas funções da
// extensão e cria uma discussão com links externos, para que a página medida
// (/d/<id>) exercite o CTA entre posts e o gate de links.
//
// Roda dentro do diretório raiz do Flarum (o workflow copia este arquivo para
// lá). Os posts entram pela API interna — o mesmo caminho de um post real —,
// então passam pelo parser e ficam com o XML que o gate espera.

declare(strict_types=1);

use Flarum\Api\Client;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

$site = require __DIR__.'/site.php';
$container = $site->bootApp()->getContainer();

$settings = $container->make(SettingsRepositoryInterface::class);
$settings->set('guest-cta.hide_links', '1');
$settings->set('guest-cta.show_post_cta', '1');
$settings->set('guest-cta.position', '2');

$admin = User::query()->where('username', 'admin')->firstOrFail();
$api = $container->make(Client::class)->withActor($admin);

/** @return array<string, mixed> */
$send = function (string $path, array $data) use ($api): array {
    $response = $api->withBody(['data' => $data])->post($path);
    $body = (string) $response->getBody();

    if ($response->getStatusCode() >= 300) {
        fwrite(STDERR, "Seed falhou em POST $path (HTTP {$response->getStatusCode()}): $body\n");
        exit(1);
    }

    return json_decode($body, true)['data'];
};

// `links.example` é o host que o smoke test do workflow procura na página do
// visitante: se aparecer lá, o gate deixou um link escapar.
$content = fn (int $i): string => "Post de teste número $i.\n\n"
    ."Um link solto: https://links.example/solto-$i\n\n"
    ."Um [link com texto](https://links.example/com-texto-$i) no meio da frase, "
    .'e um [link interno](/d/1) que deve continuar visível.';

$discussion = $send('/discussions', [
    'type' => 'discussions',
    'attributes' => ['title' => 'Guest CTA — discussão de benchmark', 'content' => $content(1)],
]);

for ($i = 2; $i <= 8; $i++) {
    $send('/posts', [
        'type' => 'posts',
        'attributes' => ['content' => $content($i)],
        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => $discussion['id']]]],
    ]);
}

// O workflow lê o id daqui para montar a URL medida.
file_put_contents(__DIR__.'/seed-discussion-id.txt', $discussion['id']);

echo "Seed concluído: discussão {$discussion['id']} com 8 posts.\n";
