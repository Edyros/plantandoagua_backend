<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Plantando Água — você planta, a árvore fica com você.</title>
    <meta name="description" content="Você planta a muda. A foto, o lugar e a árvore ficam com você.">
    <meta name="theme-color" content="#07140d">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="Plantando Água">
    <meta property="og:description" content="Você planta a muda. A foto, o lugar e a árvore ficam com você.">
    <meta property="og:image" content="{{ asset('images/hero-island.jpg') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
</head>
<body>
    <a class="skip" href="#conteudo">Pular para o conteúdo</a>

    <header class="nav on-media" id="topo">
        <div class="wrap nav-inner">
            <a class="brand" href="#topo">
                <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36">
                Plantando Água
            </a>
        </div>
    </header>

    <main id="conteudo">
        <section class="journey" id="jornada" aria-label="O Plantando Água guarda o seu plantio">
            <div class="journey-sticky">
                <div class="journey-media">
                    <video
                        class="journey-video journey-video-forward"
                        data-journey-video
                        data-src="{{ asset('videos/jornada.mp4') }}?v={{ filemtime(public_path('videos/jornada.mp4')) }}"
                        muted
                        playsinline
                        preload="none"
                        disablepictureinpicture
                        disableremoteplayback
                        tabindex="-1"
                    ></video>
                    <video
                        class="journey-video journey-video-back"
                        data-journey-reverse
                        data-src="{{ asset('videos/jornada-reverso.mp4') }}"
                        muted
                        playsinline
                        preload="none"
                        disablepictureinpicture
                        disableremoteplayback
                        tabindex="-1"
                        aria-hidden="true"
                    ></video>
                    <div class="journey-shade" aria-hidden="true"></div>
                    <div class="journey-loader" data-journey-loader>
                        <span></span>
                        <p>Preparando a imagem</p>
                    </div>
                    <button class="btn btn-ghost journey-watch" type="button" data-journey-watch>Assistir à jornada</button>
                </div>

                <div class="journey-ui">
                    <p class="journey-hint" data-journey-hint aria-hidden="true">
                        <span data-journey-hint-label>Role para acompanhar</span>
                        <span class="journey-hint-line"></span>
                    </p>

                    <div class="journey-copy">
                        <div class="journey-stages">
                            <article class="journey-stage is-active" data-start="0" data-place="west">
                                <h1>Cada muda vira uma história.</h1>
                                <p class="journey-text">O mapa salva onde ela foi plantada.</p>
                            </article>
                            <article class="journey-stage" data-start="3.4" data-place="north" aria-hidden="true" inert>
                                <h2>Não é só no dia do plantio.</h2>
                                <p class="journey-text">Quando voltar, registre uma continuação da história.</p>
                            </article>
                            <article class="journey-stage" data-start="8.05" data-place="east" aria-hidden="true" inert>
                                <h2>Cada ponto é uma muda na terra.</h2>
                                <p class="journey-text">Dá para ver onde cada pessoa plantou.</p>
                            </article>
                            <article class="journey-stage" data-start="14.15" data-sound="12.27" data-place="low" aria-hidden="true" inert>
                                <h2>O ponto nasce com a muda.</h2>
                                <p class="journey-text">No instante em que ela entra na terra.</p>
                            </article>
                            <article class="journey-stage" data-start="18.35" data-place="aside" aria-hidden="true" inert>
                                <h2>A foto é o registro da história.</h2>
                                <p class="journey-text">O primeiro ponto da história.</p>
                            </article>
                            <article class="journey-stage" data-start="21.4" data-through-end data-place="south" aria-hidden="true" inert>
                                <h2>Seu mapa começa aqui.</h2>
                                <p class="journey-text">Baixe o app e guarde o plantio.</p>
                                @include('partials.store-buttons', ['variant' => 'on-dark'])
                            </article>
                        </div>
                    </div>

                    <div class="sr-only" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Progresso da jornada" data-journey-meter></div>
                    <p class="sr-only" aria-live="polite" data-journey-live></p>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/journey.js') }}?v={{ filemtime(public_path('js/journey.js')) }}"></script>
</body>
</html>
