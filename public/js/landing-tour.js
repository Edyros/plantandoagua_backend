import { buildSky, buildStops, buildWorld } from './globe-scene.js'

const NAMES = ['nascente', 'plantio', 'qr', 'bosque', 'viveiro', 'app']
const LABELS = ['Globo', 'Plantar', 'Árvore', 'Bosque', 'Viveiro', 'App']

const motionOK = !window.matchMedia('(prefers-reduced-motion: reduce)').matches

function boot() {
    const stage = document.querySelector('.stage')
    const header = document.querySelector('header.nav')
    if (!stage || !header) return

    const menuBtn = document.querySelector('[data-menu]')
    const sticky = document.querySelector('.sticky-cta')
    const cards = [...document.querySelectorAll('[data-card]')]
    const dots = [...document.querySelectorAll('[data-dot]')]
    const hint = document.querySelector('[data-hint]')
    const live = document.querySelector('[data-live]')
    const status = document.querySelector('[data-status]')
    const prev = document.querySelector('[data-prev]')
    const next = document.querySelector('[data-next]')
    const frame = document.querySelector('.card-frame')

    const listeners = []
    let index = 0
    let announced = false

    const closeMenu = () => {
        header.classList.remove('is-open')
        menuBtn?.setAttribute('aria-expanded', 'false')
        menuBtn?.setAttribute('aria-label', 'Abrir menu')
        if (menuBtn) menuBtn.textContent = '☰'
    }

    function setStop(nextIndex, opts = {}) {
        const last = NAMES.length - 1
        index = Math.max(0, Math.min(last, nextIndex))
        cards.forEach((card, i) => {
            const on = i === index
            card.classList.toggle('is-on', on)
            card.hidden = !on
        })
        dots.forEach((dot, i) => {
            const on = i === index
            dot.classList.toggle('is-on', on)
            dot.setAttribute('aria-selected', String(on))
        })
        document.querySelectorAll('a[data-stop]').forEach((link) => {
            link.classList.toggle('is-on', Number(link.dataset.stop) === index)
        })
        if (prev) prev.disabled = index === 0
        if (next) next.disabled = index === last
        if (hint) {
            hint.textContent = index === last
                ? 'Role a página para continuar lendo.'
                : 'Role ou use as setas para girar o globo.'
        }
        const title = cards[index]?.querySelector('h1, h2')
        if (announced && live && title) live.textContent = `${LABELS[index]}. ${title.textContent.replace(/\s+/g, ' ').trim()}`
        announced = true
        if (opts.hash !== false) {
            const url = `${location.pathname}${location.search}#${NAMES[index]}`
            history.replaceState(null, '', url)
        }
        listeners.forEach((fn) => fn(index, Boolean(opts.instant)))
    }

    menuBtn?.addEventListener('click', () => {
        const open = !header.classList.contains('is-open')
        header.classList.toggle('is-open', open)
        menuBtn.setAttribute('aria-expanded', String(open))
        menuBtn.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu')
        menuBtn.textContent = open ? '✕' : '☰'
    })
    document.querySelectorAll('.mobile-nav a').forEach((link) => {
        link.addEventListener('click', closeMenu)
    })
    document.querySelectorAll('a[data-stop]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault()
            setStop(Number(link.dataset.stop))
            stage.scrollIntoView({ behavior: motionOK ? 'smooth' : 'auto', block: 'start' })
            closeMenu()
        })
    })
    dots.forEach((dot) => {
        dot.addEventListener('click', () => setStop(Number(dot.dataset.dot)))
    })
    prev?.addEventListener('click', () => setStop(index - 1))
    next?.addEventListener('click', () => setStop(index + 1))

    let wheelLock = 0
    stage.addEventListener('wheel', (event) => {
        if (Math.abs(event.deltaY) < 12) return
        const down = event.deltaY > 0
        if (frame && frame.contains(event.target) && frame.scrollHeight > frame.clientHeight + 8) {
            const atTop = frame.scrollTop <= 0
            const atBottom = frame.scrollTop + frame.clientHeight >= frame.scrollHeight - 2
            if ((down && !atBottom) || (!down && !atTop)) return
        }
        const last = NAMES.length - 1
        if ((down && index === last) || (!down && index === 0)) return
        event.preventDefault()
        const now = performance.now()
        if (now < wheelLock) return
        wheelLock = now + 680
        setStop(index + (down ? 1 : -1))
    }, { passive: false })

    let touch = null
    stage.addEventListener('touchstart', (event) => {
        if (event.target.closest('a, button, .card-frame')) {
            touch = null
            return
        }
        touch = { x: event.touches[0].clientX, y: event.touches[0].clientY }
    }, { passive: true })
    stage.addEventListener('touchend', (event) => {
        if (!touch) return
        const dx = event.changedTouches[0].clientX - touch.x
        const dy = event.changedTouches[0].clientY - touch.y
        touch = null
        if (Math.abs(dx) < 52 || Math.abs(dx) < Math.abs(dy)) return
        setStop(index + (dx < 0 ? 1 : -1))
    }, { passive: true })

    window.addEventListener('keydown', (event) => {
        const key = event.key
        if (key !== 'ArrowRight' && key !== 'ArrowLeft' && key !== 'ArrowDown' && key !== 'ArrowUp') return
        if (event.target.closest('input, textarea, select, summary')) return
        const box = stage.getBoundingClientRect()
        const looking = box.top < window.innerHeight * 0.55 && box.bottom > window.innerHeight * 0.45
        if (!looking) return
        const forward = key === 'ArrowRight' || key === 'ArrowDown'
        if (forward && index < NAMES.length - 1) {
            event.preventDefault()
            setStop(index + 1)
        } else if (!forward && index > 0) {
            event.preventDefault()
            setStop(index - 1)
        }
    })

    window.addEventListener('hashchange', () => {
        const found = NAMES.indexOf(location.hash.replace('#', ''))
        if (found < 0) return
        setStop(found, { hash: false })
        stage.scrollIntoView({ behavior: motionOK ? 'smooth' : 'auto', block: 'start' })
    })

    window.addEventListener('scroll', () => {
        header.classList.toggle('is-scrolled', window.scrollY > 8)
        const limit = stage.offsetHeight - window.innerHeight * 0.45
        sticky?.classList.toggle('is-visible', window.scrollY > limit)
    }, { passive: true })

    if ('scrollRestoration' in history) history.scrollRestoration = 'manual'
    const fromHash = NAMES.indexOf(location.hash.replace('#', ''))
    setStop(fromHash >= 0 ? fromHash : 0, { instant: true, hash: false })
    if (fromHash >= 0) window.scrollTo(0, 0)

    const api = {
        current: () => index,
        onStop(fn) { listeners.push(fn) },
    }

    import('../vendor/three/three.module.js')
        .then((THREE) => mountScene(THREE, stage, api, status))
        .catch(() => {
            stage.classList.add('is-fallback')
            if (status) status.textContent = 'Mostrando a foto do globo.'
        })
}

function mountScene(THREE, stage, api, status) {
    const canvas = document.getElementById('tour-canvas')
    let renderer
    try {
        renderer = new THREE.WebGLRenderer({
            canvas,
            antialias: true,
            alpha: false,
            powerPreference: 'high-performance',
        })
    } catch {
        stage.classList.add('is-fallback')
        if (status) status.textContent = 'Mostrando a foto do globo.'
        return
    }

    const lite = window.matchMedia('(max-width: 800px)').matches
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, lite ? 1.2 : 1.6))
    renderer.outputColorSpace = THREE.SRGBColorSpace
    renderer.toneMapping = THREE.ACESFilmicToneMapping
    renderer.toneMappingExposure = 1.05
    renderer.shadowMap.enabled = !lite
    renderer.shadowMap.type = THREE.PCFSoftShadowMap

    const scene = new THREE.Scene()
    const camera = new THREE.PerspectiveCamera(lite ? 46 : 30, 1, 0.2, 240)

    const sky = buildSky(THREE)
    scene.add(sky)

    scene.add(new THREE.HemisphereLight(0xd7ecff, 0x6a9160, 0.78))
    const sun = new THREE.DirectionalLight(0xfff4dd, 3.15)
    sun.position.set(-16, 22, 12)
    if (!lite) {
        sun.castShadow = true
        sun.shadow.mapSize.set(2048, 2048)
        sun.shadow.camera.near = 8
        sun.shadow.camera.far = 48
        sun.shadow.camera.left = -9
        sun.shadow.camera.right = 9
        sun.shadow.camera.top = 9
        sun.shadow.camera.bottom = -9
        sun.shadow.bias = -0.00035
    }
    scene.add(sun)
    const fill = new THREE.DirectionalLight(0xfff6ea, 0.85)
    fill.position.set(4, 6, 18)
    scene.add(fill)
    const rim = new THREE.DirectionalLight(0xfff1cf, 0.45)
    rim.position.set(10, 8, 18)
    scene.add(rim)

    const world = buildWorld(THREE, lite)
    scene.add(world.group)
    sun.target.position.copy(world.origin)
    scene.add(sun.target)

    const stops = buildStops(THREE, world)
    if (lite) {
        const center = world.origin
        stops[0].pos.set(center.x, center.y + 2.6, center.z + 15.2)
        stops[0].target.set(center.x, center.y - 2.4, center.z)
        stops[3].pos.set(center.x - 0.6, center.y + 6.4, center.z + 12.4)
        stops[3].target.set(center.x, center.y - 0.8, center.z)
        stops[5].pos.set(center.x + 0.3, center.y + 2.1, center.z + 15.6)
        stops[5].target.set(center.x, center.y - 2.6, center.z)
    }
    const eye = {
        pos: stops[0].pos.clone(),
        tgt: stops[0].target.clone(),
    }
    let anim = null

    function moveTo(stopIndex, instant) {
        const to = stops[stopIndex] || stops[0]
        if (instant || !motionOK) {
            eye.pos.copy(to.pos)
            eye.tgt.copy(to.target)
            anim = null
            return
        }
        anim = {
            fromP: eye.pos.clone(),
            fromT: eye.tgt.clone(),
            toP: to.pos.clone(),
            toT: to.target.clone(),
            t0: performance.now(),
            dur: 1500,
        }
    }

    api.onStop((stopIndex, instant) => {
        moveTo(stopIndex, instant)
        wake()
    })
    moveTo(api.current(), true)

    const clockShaders = []
    world.shaders.forEach((entry) => clockShaders.push(entry))

    let running = false
    let stageVisible = true
    let ready = false

    function resize() {
        const width = canvas.clientWidth || stage.clientWidth
        const height = canvas.clientHeight || stage.clientHeight
        if (!width || !height) return
        renderer.setSize(width, height, false)
        camera.aspect = width / height
        camera.updateProjectionMatrix()
    }

    function frame(now) {
        running = true
        if (document.hidden || !stageVisible) {
            running = false
            return
        }
        const time = now * 0.001
        if (anim) {
            const t = Math.min(1, (now - anim.t0) / anim.dur)
            const e = t * t * (3 - 2 * t)
            eye.pos.lerpVectors(anim.fromP, anim.toP, e)
            eye.tgt.lerpVectors(anim.fromT, anim.toT, e)
            if (t >= 1) anim = null
        }
        const sway = motionOK && !anim ? Math.sin(time * 0.45) * 0.1 : 0
        camera.position.copy(eye.pos)
        camera.position.x += sway
        camera.lookAt(eye.tgt)
        sky.position.copy(camera.position)
        clockShaders.forEach((shader) => {
            shader.uniforms.uTime.value = time
        })
        world.tick(time, camera)
        renderer.render(scene, camera)
        if (!ready) {
            ready = true
            stage.classList.add('is-ready')
            if (status) status.textContent = 'Passeio em 3D pronto.'
        }
        requestAnimationFrame(frame)
    }

    function wake() {
        if (!running) requestAnimationFrame(frame)
    }

    resize()
    new ResizeObserver(() => {
        resize()
        wake()
    }).observe(stage)
    new IntersectionObserver(([entry]) => {
        stageVisible = entry.isIntersecting
        if (stageVisible) wake()
    }, { threshold: 0.02 }).observe(stage)
    document.addEventListener('visibilitychange', wake)
    wake()
}

boot()
