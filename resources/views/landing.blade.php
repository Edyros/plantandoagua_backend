<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Plantando Água — Plante hoje. Acompanhe para sempre.</title>
    <meta name="description" content="O app que transforma cada muda em memória viva: GPS, foto, espécie nativa, mapa da comunidade, QR da árvore e lojas de mudas. Baixe na Play Store ou na App Store.">
    <meta name="theme-color" content="#0B2818">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="Plantando Água">
    <meta property="og:description" content="Registre árvores com prova de campo e veja o impacto no mapa. Disponível na Play Store e na App Store.">
    <meta property="og:image" content="{{ asset('images/hero-welcome.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body>
    <a class="skip" href="#conteudo">Pular para o conteúdo</a>

    <header class="nav" id="topo">
        <div class="wrap nav-inner">
            <a class="brand" href="#topo">
                <img src="{{ asset('images/logo.png') }}" alt="" width="42" height="42">
                Plantando Água
            </a>
            <nav class="nav-links" aria-label="Seções">
                <a href="#porque">Por que água</a>
                <a href="#demo">Demonstração</a>
                <a href="#recursos">Recursos</a>
                <a href="#como-funciona">Como funciona</a>
                <a href="#faq">Dúvidas</a>
            </nav>
            <div class="nav-cta">
                <a class="btn btn-ghost" href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer">App Store</a>
                <a class="btn btn-primary" href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer">Play Store</a>
            </div>
            <button class="menu-btn" type="button" aria-label="Abrir menu" aria-expanded="false" data-menu>☰</button>
        </div>
        <nav class="mobile-nav wrap" aria-label="Menu mobile">
            <a href="#porque">Por que água</a>
            <a href="#demo">Demonstração</a>
            <a href="#recursos">Recursos</a>
            <a href="#como-funciona">Como funciona</a>
            <a href="#faq">Dúvidas</a>
            <a class="mobile-nav-download" href="#baixar">Baixar o app</a>
        </nav>
    </header>

    <main id="conteudo">
        <section class="hero">
            <div class="hero-bg" role="img" aria-label="Vale florestado com rio e cidade ao fundo"></div>
            <div class="wrap hero-grid">
                <div>
                    <p class="kicker">Reflorestamento com prova de campo</p>
                    <h1>Plante hoje.<br>Acompanhe para sempre.</h1>
                    <p class="lede">
                        Cada muda ganha espécie, foto, GPS e um ponto no mapa. O Plantando Água registra o plantio de verdade — no campo, no minuto em que a terra fecha.
                    </p>
                    <div id="baixar" class="hero-stores">
                        @include('partials.store-buttons')
                    </div>
                    <div class="trust">
                        <span class="chip">GPS no ato do plantio</span>
                        <span class="chip">Foto de campo</span>
                        <span class="chip">Espécies nativas</span>
                        <span class="chip">QR da árvore</span>
                        <span class="chip">5 registros grátis</span>
                    </div>
                    <p class="hero-note">Depois do período gratuito, cada novo registro custa R$&nbsp;5 no próprio app, via Pix.</p>
                    <a class="hero-demo-link" href="#demo">Ver as telas do app ↓</a>
                </div>

                <div class="phone-stage" aria-label="Prévia do aplicativo">
                    <aside class="float-card float-a">
                        <b>Ipê-amarelo</b>
                        <span>Campinas · GPS e foto ok</span>
                    </aside>
                    <aside class="float-card float-b">
                        <b>Mapa da comunidade</b>
                        <span>Suas árvores e as dos outros</span>
                    </aside>
                    @include('partials.phone-demo')
                </div>
            </div>
        </section>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                <span>Ipê-amarelo · Jatobá · Pau-brasil · Araucária · Pitangueira · Juçara · Baru · Pequi · Buriti · Angico · Copaíba · Quaresmeira · Manacá-da-serra · Embaúba · Guapuruvu</span>
                <span>Ipê-amarelo · Jatobá · Pau-brasil · Araucária · Pitangueira · Juçara · Baru · Pequi · Buriti · Angico · Copaíba · Quaresmeira · Manacá-da-serra · Embaúba · Guapuruvu</span>
            </div>
        </div>

        <section class="band-dark" id="porque">
            <div class="wrap">
                <p class="section-kicker">Por que Plantando Água</p>
                <h2>Árvore no chão é chuva que fica.</h2>
                <p class="section-copy">O nome não é metáfora vazia. Floresta em pé infiltra chuva, recarrega nascente e segura o solo. O app existe para que esse gesto — plantar — deixe rastros que dá para ver, voltar e contar.</p>
                <div class="metrics">
                    <div class="metric">
                        <b data-count="{{ $stats['trees'] > 0 ? $stats['trees'] : 1 }}">{{ $stats['trees'] > 0 ? number_format($stats['trees'], 0, ',', '.') : '1' }}</b>
                        <span>{{ $stats['trees'] > 0 ? 'árvores no mapa' : 'minuto para registrar' }}</span>
                    </div>
                    <div class="metric">
                        <b>{{ $stats['species'] > 0 ? number_format($stats['species'], 0, ',', '.') : '5' }}</b>
                        <span>{{ $stats['species'] > 0 ? 'espécies registradas' : 'registros grátis para começar' }}</span>
                    </div>
                    <div class="metric">
                        <b>{{ $stats['cities'] > 0 ? number_format($stats['cities'], 0, ',', '.') : 'GPS' }}</b>
                        <span>{{ $stats['cities'] > 0 ? 'cidades no mapa' : 'e foto no instante do plantio' }}</span>
                    </div>
                    <div class="metric">
                        <b>{{ $stats['shops'] > 0 ? number_format($stats['shops'], 0, ',', '.') : 'QR' }}</b>
                        <span>{{ $stats['shops'] > 0 ? 'lojas de mudas' : 'da árvore para ler no campo' }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="wrap story">
                <figure class="story-photo" style="background-image:url('{{ asset('images/hero-welcome.png') }}')">
                    <figcaption>
                        <strong>Prova de campo, não recorte de rede social.</strong>
                        <span class="mini" style="display:block;color:#d9eadb;margin-top:4px">A foto e o ponto no mapa nascem juntos, na hora do plantio.</span>
                    </figcaption>
                </figure>
                <div>
                    <p class="section-kicker">Memória viva</p>
                    <h2>O plantio não some no dia seguinte.</h2>
                    <p class="section-copy">Planilha esquece. Foto na galeria se perde. Aqui a árvore continua: você volta, atualiza, lê o QR e vê o bosque crescer no mapa da comunidade.</p>
                    <div class="story-list">
                        <div class="story-item">
                            <div class="story-n">1</div>
                            <div><h3>Registre no local</h3><p class="section-copy">Espécie nativa ou não, quantidade, observação, foto e coordenada. Um minuto.</p></div>
                        </div>
                        <div class="story-item">
                            <div class="story-n">2</div>
                            <div><h3>Veja no mapa</h3><p class="section-copy">Filtro seus plantios, a comunidade ou as lojas de mudas. Cada ponto abre a história da árvore.</p></div>
                        </div>
                        <div class="story-item">
                            <div class="story-n">3</div>
                            <div><h3>Volte anos depois</h3><p class="section-copy">Atualize a muda, acompanhe medalhas e leia o QR se a árvore estiver marcada no campo.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="band-mist" id="demo">
            <div class="wrap">
                <p class="section-kicker">Demonstração</p>
                <h2>As cinco telas do dia a dia.</h2>
                <p class="section-copy">O mesmo fluxo do app: impacto, mapa, plantio, lojas e perfil. Toque para percorrer.</p>
                <div class="demo-layout" style="margin-top:28px">
                    <div class="phone-stage">
                        @include('partials.phone-demo')
                    </div>
                    <div>
                        <div class="demo-switch" role="tablist" aria-label="Telas do app" style="justify-content:flex-start">
                            <button type="button" class="on" data-go="home">Início</button>
                            <button type="button" data-go="map">Mapa</button>
                            <button type="button" data-go="plant">Plantar</button>
                            <button type="button" data-go="shops">Lojas</button>
                            <button type="button" data-go="profile">Perfil</button>
                        </div>
                        <p class="demo-caption" data-caption style="text-align:left;margin-top:22px;font-size:1.05rem">
                            <strong>Aqui é o Início</strong>
                            Seu impacto, a meta do mês e quantos registros ainda restam. O atalho verde abre um plantio novo.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="recursos">
            <div class="wrap">
                <p class="section-kicker">O aplicativo</p>
                <h2>Feito para quem planta de verdade.</h2>
                <p class="section-copy">Não é um mural de fotos. É um registro com evidência, catálogo de nativas, mapa compartilhado e um caminho para quem vende muda.</p>
                <div class="bento">
                    <article class="tile tile-wide">
                        <p class="section-kicker" style="color:#c8e6c9">No campo</p>
                        <h3 style="font-family:Fraunces,serif;font-size:2rem;letter-spacing:-.03em">Foto + GPS no mesmo segundo</h3>
                        <p>A prova nasce quando a muda entra na terra. Sem recorte posterior, sem pin jogado no sofá.</p>
                    </article>
                    <article class="tile">
                        <div class="ico">🍃</div>
                        <h3>Catálogo de nativas</h3>
                        <p>Ipê, jatobá, araucária, juçara, baru, pequi. O app sugere a espécie certa na hora de registrar.</p>
                    </article>
                    <article class="tile">
                        <div class="ico">🗺️</div>
                        <h3>Mapa da comunidade</h3>
                        <p>Meus plantios, o bosque coletivo ou as lojas. Cada árvore vira um ponto que dá para filtrar.</p>
                    </article>
                    <article class="tile">
                        <div class="ico">QR</div>
                        <h3>QR da árvore</h3>
                        <p>Marque a muda no campo e leia depois: espécie, data, foto e histórico de atualizações.</p>
                    </article>
                    <article class="tile">
                        <div class="ico">🏪</div>
                        <h3>Lojas de mudas</h3>
                        <p>Encontre viveiro perto de você. Se você vende, cadastre a loja no mesmo mapa.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="band-mist">
            <div class="wrap split">
                <article class="panel">
                    <p class="section-kicker">Medalhas</p>
                    <h3>O bosque também vira jogo.</h3>
                    <p class="section-copy">Não é ranking de vaidade: é hábito. Primeira Raiz, Amigo das Nativas, Mapa Vivo, Floresta Pessoal.</p>
                    <div class="badges">
                        <div class="badge"><b>Primeira Raiz</b><span>A primeira árvore no mapa</span></div>
                        <div class="badge"><b>Amigo das Nativas</b><span>3 espécies nativas diferentes</span></div>
                        <div class="badge"><b>Mapa Vivo</b><span>Plantios em 5 áreas distintas</span></div>
                        <div class="badge"><b>Floresta Pessoal</b><span>100 árvores ao longo dos dias</span></div>
                    </div>
                </article>
                <article class="panel">
                    <p class="section-kicker">Para quem vende</p>
                    <h3>O viveiro entra no mapa.</h3>
                    <p class="section-copy">Mudas, jardinagem, adubo, ferramentas, irrigação. A loja aparece para quem está prestes a plantar — no mesmo app do registro.</p>
                    <div class="badges">
                        <div class="badge"><b>Perto de mim</b><span>Quem planta acha quem vende</span></div>
                        <div class="badge"><b>Sua vitrine</b><span>Produtos, cidade e contato</span></div>
                    </div>
                </article>
            </div>
        </section>

        <section id="como-funciona">
            <div class="wrap">
                <p class="section-kicker">Como funciona</p>
                <h2>Do download à primeira muda.</h2>
                <div class="steps">
                    <article class="step">
                        <div class="step-n">1</div>
                        <h3>Baixe o app</h3>
                        <p>Play Store ou App Store. Conta gratuita, em português, feito para o campo brasileiro.</p>
                    </article>
                    <article class="step">
                        <div class="step-n">2</div>
                        <h3>Plante no local</h3>
                        <p>Abra Plantar, escolha a espécie, tire a foto e deixe o GPS gravar o ponto. Pronto.</p>
                    </article>
                    <article class="step">
                        <div class="step-n">3</div>
                        <h3>Acompanhe o mapa</h3>
                        <p>Sua árvore entra no bosque da comunidade. Filtre, compartilhe o perfil, volte depois.</p>
                    </article>
                    <article class="step">
                        <div class="step-n">4</div>
                        <h3>Continue o hábito</h3>
                        <p>Meta do mês, medalhas e, se quiser, o QR na muda. Os 5 primeiros registros são livres.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="band-mist">
            <div class="wrap">
                <p class="section-kicker">Para quem é</p>
                <h2>Quem planta, quem vende, quem ensina.</h2>
                <div class="who">
                    <article>
                        <div class="ico">🌱</div>
                        <h3>Quem planta no fim de semana</h3>
                        <p>Um registro rápido, com evidência, que não depende de planilha nem de grupo no WhatsApp.</p>
                    </article>
                    <article>
                        <div class="ico">🏫</div>
                        <h3>Escola, ONG e mutirão</h3>
                        <p>Várias mãos, o mesmo mapa. Cada turma deixa as árvores visíveis depois que a ação acaba.</p>
                    </article>
                    <article>
                        <div class="ico">🏡</div>
                        <h3>Viveiro e loja de mudas</h3>
                        <p>Apareça para quem está com a cova aberta. Cadastre a loja e os produtos no mapa.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="faq">
            <div class="wrap">
                <p class="section-kicker">Dúvidas</p>
                <h2>Antes de baixar.</h2>
                <div class="faq">
                    <details open>
                        <summary>O Plantando Água é um site ou um aplicativo?</summary>
                        <p>Este site apresenta o produto. O registro de plantio, o mapa e as lojas vivem no aplicativo, na Play Store e na App Store.</p>
                    </details>
                    <details>
                        <summary>Precisa de internet no campo?</summary>
                        <p>O plantio usa GPS e câmera no celular. O app guarda o registro no aparelho e sincroniza quando a rede volta.</p>
                    </details>
                    <details>
                        <summary>É grátis?</summary>
                        <p>Criar conta é grátis. Os 5 primeiros registros de plantio não têm custo. Depois, cada liberação custa R$ 5, paga no app via Pix.</p>
                    </details>
                    <details>
                        <summary>Como o app prova que a árvore foi plantada?</summary>
                        <p>Foto e coordenada entram juntos, no momento do registro. Depois dá para atualizar a muda e ler o QR se ela estiver marcada no local.</p>
                    </details>
                    <details>
                        <summary>Consigo cadastrar um viveiro?</summary>
                        <p>Sim. Em Lojas você encontra parceiros ou cadastra a sua, com cidade, contato e o que vende: mudas, adubo, ferramentas, irrigação.</p>
                    </details>
                </div>
            </div>
        </section>

        <section>
            <div class="wrap">
                <div class="cta">
                    <p class="section-kicker" style="color:#c8e6c9">Chamada para ação</p>
                    <h2>Leve o bosque no bolso.</h2>
                    <p>O site conta a história. O app registra a árvore. Baixe na loja do seu celular e faça o próximo plantio valer no mapa.</p>
                    @include('partials.store-buttons', ['variant' => 'on-dark'])
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="wrap foot">
            <div class="foot-brand">
                <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36">
                Plantando Água
            </div>
            <span>© {{ date('Y') }} · plantandoagua.com · o app vive nas lojas</span>
        </div>
    </footer>

    <div class="sticky-cta" aria-label="Baixar o aplicativo">
        @include('partials.store-buttons')
    </div>

    <script>
        const header = document.querySelector('header.nav');
        const menuBtn = document.querySelector('[data-menu]');
        const apps = [...document.querySelectorAll('.app')];
        const tabs = [...document.querySelectorAll('.tabs [data-tab]')];
        const switches = [...document.querySelectorAll('.demo-switch [data-go]')];
        const caption = document.querySelector('[data-caption]');
        const order = ['home', 'map', 'plant', 'shops', 'profile'];
        const copy = {
            home: ['Aqui é o Início', 'Seu impacto, a meta do mês e quantos registros ainda restam. O atalho verde abre um plantio novo.'],
            map: ['O mapa da comunidade', 'Cada árvore vira um ponto. Filtre os seus plantios, a comunidade ou as lojas parceiras.'],
            plant: ['Plantar leva 1 minuto', 'Espécie, foto e GPS na hora do plantio. Assim a árvore entra no mapa com prova de campo.'],
            shops: ['Lojas de mudas', 'Encontre parceiros perto de você. Se você vende mudas, dá para cadastrar a sua loja no mapa.'],
            profile: ['Seu perfil', 'Complete cidade e foto, leia o QR de uma árvore, veja medalhas e ajuste o app.'],
        };
        let current = 0;
        let timer;

        const sticky = document.querySelector('.sticky-cta');
        const setMenu = (open) => {
            header.classList.toggle('is-open', open);
            menuBtn?.setAttribute('aria-expanded', String(open));
            if (menuBtn) menuBtn.textContent = open ? '✕' : '☰';
        };

        window.addEventListener('scroll', () => {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
            sticky?.classList.toggle('is-visible', window.scrollY > 380);
        }, { passive: true });
        menuBtn?.addEventListener('click', () => setMenu(!header.classList.contains('is-open')));
        document.querySelectorAll('.mobile-nav a').forEach((link) => {
            link.addEventListener('click', () => setMenu(false));
        });

        function show(name) {
            current = Math.max(0, order.indexOf(name));
            apps.forEach((el) => el.classList.toggle('is-on', el.dataset.screen === name));
            tabs.forEach((el) => el.classList.toggle('on', el.dataset.tab === name));
            switches.forEach((el) => el.classList.toggle('on', el.dataset.go === name));
            const text = copy[name];
            if (caption && text) {
                caption.innerHTML = `<strong>${text[0]}</strong>${text[1]}`;
            }
        }
        function tick() {
            current = (current + 1) % order.length;
            show(order[current]);
        }
        function restart() {
            clearInterval(timer);
            timer = setInterval(tick, 4800);
        }
        tabs.forEach((el) => el.addEventListener('click', () => { show(el.dataset.tab); restart(); }));
        switches.forEach((el) => el.addEventListener('click', () => { show(el.dataset.go); restart(); }));
        restart();
    </script>
</body>
</html>
