<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Plantando Água — plante e ajude o mundo a melhorar.</title>
    <meta name="description" content="Você planta a muda. A foto, o lugar e a árvore ficam com você — e o mundo fica um pouco melhor.">
    <meta name="theme-color" content="#1b2820">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/index-novo.css') }}?v={{ filemtime(public_path('css/index-novo.css')) }}">
</head>
<body>
    <a class="skip" href="#conteudo">Pular para o conteúdo</a>

    <header class="nav" data-header id="topo">
        <a class="brand" href="#topo">
            <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36">
            Plantando Água
        </a>
        <nav class="nav-links" id="menu" data-nav-menu>
            <a href="#jornada">A jornada</a>
            <a href="#aplicativo">O aplicativo</a>
            <a href="#campanhas">Campanhas</a>
            <a href="#baixar">Baixar</a>
        </nav>
        <a class="nav-cta" href="#baixar">Baixar o app</a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="menu">
            <span class="sr-only">Abrir menu</span>
            <span></span>
            <span></span>
        </button>
    </header>

    <main id="conteudo">
        <section class="hero" aria-label="Apresentação">
            @include('partials.image-slot', [
                'file' => '01-hero',
                'title' => 'Paisagem de mata com uma muda em primeiro plano, luz de manhã',
                'mode' => 'label',
                'class' => 'slot-hero',
            ])
            <div class="hero-copy">
                <h1>Cada muda ajuda o mundo a melhorar.</h1>
                <hr class="rule">
                <p class="lede">Você planta. A foto, o lugar e a árvore ficam com você.</p>
                <a class="btn btn-line" href="#jornada">Ver a jornada</a>
            </div>
        </section>

        <section class="grow" data-grow aria-label="A foto toma a tela">
            <div class="grow-pin">
                <div class="grow-frame">
                    @include('partials.image-slot', [
                        'file' => '02-tela',
                        'title' => 'Muda no centro do quadro, a paisagem abrindo ao redor',
                        'class' => 'grow-slot',
                    ])
                    <div class="grow-copy">
                        <p class="kicker">O gesto</p>
                        <h2>Uma muda muda o lugar.</h2>
                        <p>Plantar é cuidar do mundo ao seu redor. A foto, o lugar e a árvore ficam com você.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="story" id="jornada" data-story aria-label="Quatro etapas do plantio">
            <div class="story-pin">
                <div class="story-viewport">
                    <div class="story-track">
                        <article class="beat beat-intro">
                            <div class="beat-intro-copy">
                                <p class="eyebrow">A jornada</p>
                                <h2>Quatro etapas.</h2>
                                <p>Da muda na terra até o ponto no mapa. Um jeito simples de cuidar do mundo.</p>
                                <p class="beat-names">Plantar · Fotografar · Voltar · <em>O mapa</em></p>
                            </div>
                        </article>
                        <article class="beat">
                            <div class="beat-media">
                                @include('partials.image-slot', ['file' => '03-plantar', 'title' => 'Muda entrando na terra, vista de perto', 'class' => 'beat-slot'])
                            </div>
                            <span class="beat-num" aria-hidden="true">01</span>
                            <div class="beat-copy">
                                <p class="kicker">Plantar</p>
                                <h2>O ponto nasce com a muda.</h2>
                                <p>No instante em que ela entra na terra, o cuidado vira um lugar.</p>
                            </div>
                        </article>
                        <article class="beat">
                            <div class="beat-media">
                                @include('partials.image-slot', ['file' => '04-fotografar', 'title' => 'Celular fotografando a muda recém-plantada', 'class' => 'beat-slot'])
                            </div>
                            <span class="beat-num" aria-hidden="true">02</span>
                            <div class="beat-copy">
                                <p class="kicker">Fotografar</p>
                                <h2>A foto fica com a muda.</h2>
                                <p>O primeiro registro, no lugar em que ela entrou na terra.</p>
                            </div>
                        </article>
                        <article class="beat">
                            <div class="beat-media">
                                @include('partials.image-slot', ['file' => '05-voltar', 'title' => 'Pessoa de costas admirando a árvore que plantou', 'class' => 'beat-slot'])
                            </div>
                            <span class="beat-num" aria-hidden="true">03</span>
                            <div class="beat-copy">
                                <p class="kicker">Voltar</p>
                                <h2>A história continua.</h2>
                                <p>Não é só no dia do plantio. A continuação fica no mesmo ponto.</p>
                            </div>
                        </article>
                        <article class="beat">
                            <div class="beat-media">
                                @include('partials.image-slot', ['file' => '06-mapa', 'title' => 'Mapa com o seu ponto e os pontos da comunidade', 'class' => 'beat-slot'])
                            </div>
                            <span class="beat-num" aria-hidden="true">04</span>
                            <div class="beat-copy">
                                <p class="kicker">O mapa</p>
                                <h2>Onde cada pessoa cuidou do mundo.</h2>
                                <p>Cada ponto é uma muda na terra.</p>
                            </div>
                        </article>
                    </div>
                </div>
                <ol class="story-steps">
                    <li>01 Plantar</li>
                    <li>02 Fotografar</li>
                    <li>03 Voltar</li>
                    <li>04 O mapa</li>
                </ol>
            </div>
        </section>

        <section class="band" id="aplicativo">
            <div class="section-head">
                <div class="intro">
                    <p class="eyebrow">O aplicativo</p>
                    <h2>Cuide do mundo. Guarde o que você fez.</h2>
                    <p>Você planta a muda. O mapa guarda o lugar. Quando você volta, a história continua — com outra foto, no mesmo ponto.</p>
                </div>
                <div class="cards-nav">
                    <button type="button" data-cards-prev aria-label="Cartões anteriores">←</button>
                    <button type="button" data-cards-next aria-label="Próximos cartões">→</button>
                </div>
            </div>
            <div class="cards" tabindex="0" aria-label="O que o aplicativo guarda">
                <article class="card">
                    @include('partials.image-slot', ['file' => '07-card-mapa', 'title' => 'Ponto no mapa sobre a paisagem do plantio', 'class' => 'slot-card'])
                    <h3>Mapa</h3>
                    <p>O lugar da muda fica salvo.</p>
                </article>
                <article class="card">
                    @include('partials.image-slot', ['file' => '08-card-foto', 'title' => 'Close da muda na terra, recém-plantada', 'class' => 'slot-card'])
                    <h3>Foto</h3>
                    <p>O instante em que ela entra na terra.</p>
                </article>
                <article class="card">
                    @include('partials.image-slot', ['file' => '09-card-continuacao', 'title' => 'Árvore já crescida, fotografada de novo no mesmo lugar', 'class' => 'slot-card'])
                    <h3>Continuação</h3>
                    <p>A volta ao mesmo ponto.</p>
                </article>
                <article class="card">
                    @include('partials.image-slot', ['file' => '10-card-campanha', 'title' => 'Grupo plantando dentro de uma área marcada no mapa', 'class' => 'slot-card'])
                    <h3>Campanha</h3>
                    <p>Várias pessoas, uma área.</p>
                </article>
                <article class="card">
                    @include('partials.image-slot', ['file' => '11-card-loja', 'title' => 'Viveiro com fileiras de mudas nativas', 'class' => 'slot-card'])
                    <h3>Loja</h3>
                    <p>Mudas, jardinagem, adubo, ferramentas e irrigação no mapa.</p>
                </article>
                <article class="card">
                    @include('partials.image-slot', ['file' => '12-card-comunidade', 'title' => 'Mapa com muitos pontos, cada um uma muda', 'class' => 'slot-card'])
                    <h3>Comunidade</h3>
                    <p>Gente cuidando do mesmo mundo.</p>
                </article>
            </div>
        </section>

        <section class="statements" data-rotator data-motion="slide" data-interval="4600" aria-label="O que fica registrado">
            <div class="statement-stage">
                <p class="statement is-active" data-item>Você planta a muda.</p>
                <p class="statement" data-item aria-hidden="true" inert>O mundo fica um pouco melhor.</p>
                <p class="statement" data-item aria-hidden="true" inert>A foto fica com você.</p>
            </div>
            <div class="dots" role="tablist" aria-label="Frases">
                <button class="dot is-active" type="button" data-tab aria-label="Você planta a muda" aria-selected="true"></button>
                <button class="dot" type="button" data-tab aria-label="O mundo fica um pouco melhor" aria-selected="false" tabindex="-1"></button>
                <button class="dot" type="button" data-tab aria-label="A foto fica com você" aria-selected="false" tabindex="-1"></button>
            </div>
        </section>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                @foreach ([1, 2] as $copy)
                    @foreach ([
                        ['13-faixa-muda', 'Muda na terra, vista de perto'],
                        ['14-faixa-celular', 'Celular registrando o plantio'],
                        ['15-faixa-ponto', 'Ponto novo no mapa'],
                        ['16-faixa-arvore', 'A mesma árvore meses depois'],
                        ['17-faixa-area', 'Área da campanha vista de cima'],
                        ['18-faixa-viveiro', 'Viveiro com mudas em fileira'],
                    ] as [$file, $title])
                        @include('partials.image-slot', ['file' => $file, 'title' => $title, 'class' => 'marquee-item'])
                    @endforeach
                @endforeach
            </div>
        </div>

        <div class="ticker" aria-hidden="true">
            <div class="ticker-track">
                @foreach ([1, 2] as $copy)
                    <p>Mapa <span>·</span> Foto <span>·</span> História <span>·</span> Campanha <span>·</span> Área <span>·</span> Loja <span>·</span> Adoção <span>·</span> Comunidade <span>·</span></p>
                @endforeach
            </div>
        </div>

        <section class="push-grid" aria-label="Campanhas e o seu mapa">
            <article class="push" id="campanhas">
                @include('partials.image-slot', [
                    'file' => '19-campanha',
                    'title' => 'Pessoas plantando mudas numa área combinada, vistas de cima',
                    'mode' => 'label',
                ])
                <div class="push-copy">
                    <p class="eyebrow">Campanhas</p>
                    <h2>Um lugar melhor, feito por várias pessoas.</h2>
                    <p>A campanha marca uma área no mapa. Quem participa planta ali, e cada muda ajuda aquele lugar a melhorar.</p>
                    <a class="btn btn-line" href="#campo">Como o ponto nasce</a>
                </div>
            </article>
            <article class="push">
                @include('partials.image-slot', [
                    'file' => '20-seu-mapa',
                    'title' => 'Pessoa ao ar livre olhando o próprio mapa no celular',
                    'mode' => 'label',
                ])
                <div class="push-copy">
                    <p class="eyebrow">O seu mapa</p>
                    <h2>Seu pedaço de mundo começa aqui.</h2>
                    <p>Baixe o app e guarde o plantio. Dá para plantar ou adotar: o lugar fica com você.</p>
                    @include('partials.store-buttons')
                </div>
            </article>
        </section>

        <section class="split" id="campo">
            @include('partials.image-slot', [
                'file' => '21-campo',
                'title' => 'Pessoa ajoelhada plantando, com o celular ao lado registrando o lugar',
                'class' => 'slot-split',
            ])
            <div class="split-copy">
                <p class="eyebrow">No campo</p>
                <h2>O ponto nasce com a muda.</h2>
                <p>No instante em que ela entra na terra, o mapa marca o lugar. A espécie, a foto e o que você anotar ficam nesse ponto.</p>
                <p>Plantar ou adotar é um jeito de cuidar do mundo de perto. Quando você volta, registra uma continuação: outra foto, outra nota, a mesma história.</p>
            </div>
        </section>

        <section class="band band-tight">
            <div class="intro">
                <p class="eyebrow">A jornada</p>
                <h2>Do dia do plantio a um mundo um pouco melhor.</h2>
            </div>
            <div class="moments">
                <article class="moment">
                    @include('partials.image-slot', ['file' => '22-dia', 'title' => 'Cova aberta com a muda sendo firmada na terra', 'class' => 'slot-moment'])
                    <p class="eyebrow">01</p>
                    <h3>O dia do plantio</h3>
                    <p>A muda entra na terra e o ponto nasce.</p>
                </article>
                <article class="moment">
                    @include('partials.image-slot', ['file' => '23-volta', 'title' => 'Mãos junto a um galho já com folhas, no lugar do plantio', 'class' => 'slot-moment'])
                    <p class="eyebrow">02</p>
                    <h3>A volta</h3>
                    <p>Não é só no dia do plantio. A continuação fica no mesmo ponto.</p>
                </article>
                <article class="moment">
                    @include('partials.image-slot', ['file' => '24-mapa-regiao', 'title' => 'Mapa da região com pontos de plantio sobre o verde', 'class' => 'slot-moment'])
                    <p class="eyebrow">03</p>
                    <h3>O mapa</h3>
                    <p>Cada ponto é uma muda. Dá para ver onde cada pessoa escolheu cuidar.</p>
                </article>
            </div>
        </section>

        <div class="ticker ticker-foot" aria-hidden="true">
            <div class="ticker-track">
                @foreach ([1, 2] as $copy)
                    <p>Plantar <span>·</span> Cuidar do lugar <span>·</span> Guardar a história <span>·</span> Voltar <span>·</span> Melhorar o mundo <span>·</span></p>
                @endforeach
            </div>
        </div>
    </main>

    <footer class="footer" id="baixar">
        <p class="eyebrow">Plantando Água</p>
        <h2>O mundo melhora a partir daqui.</h2>
        <p>Baixe o app, plante a muda e guarde o lugar.</p>
        @include('partials.store-buttons', ['variant' => 'on-dark'])
        <nav class="footer-links" aria-label="Rodapé">
            <a href="#jornada">A jornada</a>
            <a href="#aplicativo">O aplicativo</a>
            <a href="#campanhas">Campanhas</a>
            <a href="#topo">Topo</a>
        </nav>
    </footer>

    <script src="{{ asset('js/index-novo.js') }}?v={{ filemtime(public_path('js/index-novo.js')) }}"></script>
</body>
</html>
