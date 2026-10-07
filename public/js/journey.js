(() => {
    const journey = document.querySelector('.journey')
    const video = document.querySelector('[data-journey-video]')
    const reverse = document.querySelector('[data-journey-reverse]')
    if (!journey || !video) return

    const stages = [...journey.querySelectorAll('.journey-stage')].map((el) => ({
        el,
        start: Number(el.dataset.start) || 0,
    }))
    const steps = [...journey.querySelectorAll('.journey-timeline li')]
    const fill = journey.querySelector('[data-journey-fill]')
    const meter = journey.querySelector('[data-journey-meter]')
    const live = journey.querySelector('[data-journey-live]')
    const hint = journey.querySelector('[data-journey-hint]')
    const hintLabel = journey.querySelector('[data-journey-hint-label]')
    const loader = journey.querySelector('[data-journey-loader]')
    const watch = journey.querySelector('[data-journey-watch]')
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    const FALLBACK = 24.08
    let current = 0
    let announced = false
    let hintMode = 'start'
    let percent = -1
    let want = 0
    let seekTimer = 0
    let snapLock = false

    video.muted = true
    video.playsInline = true
    if (reverse) {
        reverse.muted = true
        reverse.playsInline = true
    }

    const duration = () => {
        const value = video.duration
        return Number.isFinite(value) && value > 0 ? value : FALLBACK
    }

    const endTime = () => Math.max(0.04, duration() - 0.08)

    const soundFrom = () => {
        const node = journey.querySelector('[data-sound]')
        const value = Number(node?.dataset.sound)
        return Number.isFinite(value) ? value : Infinity
    }

    const syncSound = (time) => {
        const audible = time + 0.03 >= soundFrom()
        const apply = (media) => {
            if (!media) return
            const wasQuiet = media.muted
            media.muted = !audible
            if (!audible) return
            media.volume = 1
            if (!wasQuiet || media.paused) return
            const pending = media.play()
            if (pending && typeof pending.catch === 'function') pending.catch(() => {})
        }
        apply(video)
        apply(reverse)
    }

    const markEnded = (time) => {
        const atEnd = time >= endTime() - 0.12
        if (!atEnd) journey.classList.remove('is-ended')
        else if (!snapLock) journey.classList.add('is-ended')
    }

    const cue = (index) => {
        const stage = stages[index]
        if (!stage) return 0
        if (stage.el.hasAttribute('data-through-end')) return endTime()
        return stage.start
    }

    const indexAt = (time) => {
        let index = 0
        stages.forEach((stage, i) => {
            if (time >= stage.start) index = i
        })
        return index
    }

    const fail = () => {
        journey.classList.add('is-error')
        journey.classList.remove('is-ready')
        const label = loader?.querySelector('p')
        if (label) label.textContent = 'Não foi possível carregar o vídeo.'
    }

    const markReady = () => {
        if (!journey.classList.contains('is-error')) journey.classList.add('is-ready')
    }

    const loadClip = async (el) => {
        const url = el.dataset.src
        if (!url) throw new Error('video')
        const response = await fetch(url)
        if (!response.ok || !response.body) throw new Error('video')
        const reader = response.body.getReader()
        const chunks = []
        while (true) {
            const { done, value } = await reader.read()
            if (done) break
            if (value) chunks.push(value)
        }
        const blob = new Blob(chunks, { type: 'video/mp4' })
        if (!blob.size) throw new Error('video')
        el.src = URL.createObjectURL(blob)
        if (el.readyState < 2) {
            await new Promise((resolve, reject) => {
                el.addEventListener('loadeddata', () => resolve(), { once: true })
                el.addEventListener('error', () => reject(new Error('video')), { once: true })
            })
        }
    }

    const mountVideo = async (withReverse) => {
        const jobs = [loadClip(video)]
        if (withReverse && reverse) {
            jobs.push(loadClip(reverse).catch(() => {
                reverse.dataset.broken = '1'
            }))
        }
        await Promise.all(jobs)
        markReady()
    }

    if (reduce) {
        stages.forEach((stage) => {
            stage.el.classList.add('is-active')
            stage.el.removeAttribute('aria-hidden')
            stage.el.inert = false
        })
        steps.forEach((step) => step.classList.add('is-done'))
        if (fill) fill.style.transform = 'scaleX(1)'
        const followSound = () => {
            syncSound(video.currentTime || 0)
            markEnded(video.currentTime || 0)
        }
        video.addEventListener('timeupdate', followSound)
        video.addEventListener('ended', () => {
            journey.classList.add('is-ended')
            if (watch) watch.textContent = 'Assistir à jornada'
        })
        watch?.addEventListener('click', () => {
            if (video.paused) {
                if (video.ended || video.currentTime >= endTime() - 0.12) {
                    try { video.currentTime = 0 } catch (error) {}
                    journey.classList.remove('is-ended')
                    syncSound(0)
                }
                const pending = video.play()
                const started = () => { watch.textContent = 'Pausar' }
                if (pending && typeof pending.then === 'function') pending.then(started).catch(() => {})
                else started()
            } else {
                video.pause()
                watch.textContent = 'Assistir à jornada'
            }
        })
        mountVideo(false).catch(fail)
        return
    }

    const showStage = (next) => {
        if (next === current && announced) return
        const previous = current
        stages.forEach((stage, i) => {
            const on = i === next
            const leaving = announced && i === previous && i !== next
            stage.el.classList.toggle('is-active', on)
            stage.el.classList.toggle('is-leaving', leaving)
            if (on) {
                stage.el.removeAttribute('aria-hidden')
                stage.el.inert = false
            } else {
                stage.el.setAttribute('aria-hidden', 'true')
                stage.el.inert = true
            }
        })
        steps.forEach((step, i) => {
            step.classList.toggle('is-on', i === next)
            step.classList.toggle('is-done', i < next)
            const button = step.querySelector('button')
            if (!button) return
            if (i === next) button.setAttribute('aria-current', 'step')
            else button.removeAttribute('aria-current')
        })
        if (announced && next !== previous && live) {
            const title = stages[next].el.querySelector('h1, h2')
            if (title) live.textContent = title.textContent.replace(/\s+/g, ' ').trim()
        }
        current = next
        announced = true
    }

    const syncHint = (index) => {
        let mode = 'hidden'
        if (index <= 0) mode = 'start'
        if (mode === hintMode) return
        hintMode = mode
        if (hintLabel) hintLabel.textContent = 'Role para acompanhar'
        hint?.classList.toggle('is-gone', mode === 'hidden')
    }

    const note = (time) => {
        want = time
        syncSound(time)
        markEnded(time)
        const index = indexAt(time)
        showStage(index)
        syncHint(index)
        const limit = endTime()
        const pct = Math.round((limit > 0 ? time / limit : 0) * 100)
        if (meter && pct !== percent) {
            percent = pct
            meter.setAttribute('aria-valuenow', String(Math.min(100, Math.max(0, pct))))
        }
    }

    const playTo = (to) => {
        if (snapLock) return
        const from = video.readyState >= 2 ? video.currentTime : (stages[current]?.start || 0)
        const delta = to - from
        const travel = Math.abs(delta)
        if (video.readyState < 2 || travel < 0.05) {
            if (video.readyState >= 1) {
                try { video.currentTime = Math.max(0, to) } catch (error) {}
            }
            note(to)
            return
        }

        snapLock = true
        seekTimer += 1
        journey.classList.add('is-driving')
        const generation = seekTimer
        let closed = false
        let usedReverse = false
        let safety = 0

        const finish = () => {
            if (finish.done) return
            finish.done = true
            journey.classList.remove('is-driving', 'is-reversing')
            want = to
            note(to)
            if (to >= endTime() - 0.12) journey.classList.add('is-ended')
            window.setTimeout(() => { snapLock = false }, 180)
        }

        const release = () => {
            if (closed || generation !== seekTimer) return
            closed = true
            window.clearTimeout(safety)
            video.pause()
            video.playbackRate = 1
            if (reverse) {
                reverse.pause()
                reverse.playbackRate = 1
            }
            if (usedReverse && video.readyState >= 1 && Math.abs(video.currentTime - to) > 0.08) {
                let landed = false
                const done = () => {
                    if (landed) return
                    landed = true
                    video.removeEventListener('seeked', done)
                    finish()
                }
                video.addEventListener('seeked', done)
                window.setTimeout(done, 1200)
                try { video.currentTime = Math.max(0, to) } catch (error) { done() }
                return
            }
            finish()
        }

        safety = window.setTimeout(release, Math.round(travel * 1200 + 2800))

        const reverseUsable = delta < 0 && reverse
            && reverse.dataset.broken !== '1'
            && reverse.readyState >= 2
            && reverse.duration > 0

        const lead = reverseUsable ? reverse : video
        const unlock = lead.play()
        if (unlock && typeof unlock.catch === 'function') unlock.catch(() => {})

        const seekMedia = (media, time, fn) => {
            if (media.readyState >= 1 && Math.abs(media.currentTime - time) <= 0.12) {
                fn()
                return
            }
            let done = false
            const go = () => {
                if (done || generation !== seekTimer) return
                done = true
                media.removeEventListener('seeked', go)
                window.clearTimeout(timer)
                if (closed) return
                if (Math.abs(media.currentTime - time) > 0.18) {
                    release()
                    return
                }
                fn()
            }
            media.addEventListener('seeked', go)
            const timer = window.setTimeout(go, 2200)
            try {
                media.pause()
                media.currentTime = Math.max(0, time)
            } catch (error) {
                go()
            }
        }

        const runPlayback = (media, startAt, stopAt, readTime, reversing) => {
            const arm = () => {
                if (closed || generation !== seekTimer) return
                if (reversing) {
                    usedReverse = true
                    journey.classList.add('is-reversing')
                    if (Math.abs(video.currentTime - to) > 0.08) {
                        try { video.currentTime = Math.max(0, to) } catch (error) {}
                    }
                }
                media.playbackRate = 1
                let last = media.currentTime
                let mark = performance.now()
                let seen = false
                const step = () => {
                    if (closed || generation !== seekTimer) return
                    const now = performance.now()
                    const mediaTime = media.currentTime
                    if (Math.abs(mediaTime - last) > 0.0008) {
                        seen = true
                        last = mediaTime
                        mark = now
                    } else if ((seen && now - mark > 700) || (!seen && now - mark > 1600)) {
                        release()
                        return
                    }
                    note(readTime())
                    if (media.ended || mediaTime >= stopAt - 0.025) {
                        release()
                        return
                    }
                    requestAnimationFrame(step)
                }
                const kick = () => {
                    if (closed || generation !== seekTimer) return
                    media.playbackRate = 1
                    requestAnimationFrame(step)
                }
                const pending = media.play()
                const retryMuted = () => {
                    media.muted = true
                    const again = media.play()
                    if (again && typeof again.then === 'function') again.then(kick).catch(() => release())
                    else kick()
                }
                if (pending && typeof pending.then === 'function') pending.then(kick).catch(retryMuted)
                else kick()
            }
            seekMedia(media, startAt, arm)
        }

        video.pause()
        video.playbackRate = 1
        if (reverse) {
            reverse.pause()
            reverse.playbackRate = 1
        }

        if (reverseUsable) {
            const span = duration()
            const length = reverse.duration
            const reverseTimeFor = (journeyTime) => Math.min(length, Math.max(0, length * (1 - journeyTime / span)))
            runPlayback(
                reverse,
                reverseTimeFor(from),
                reverseTimeFor(to),
                () => Math.max(0, span * (1 - reverse.currentTime / length)),
                true,
            )
            return
        }

        runPlayback(video, from, to, () => video.currentTime, false)
    }

    const onStage = () => {
        const rect = journey.getBoundingClientRect()
        return rect.top < 48 && rect.bottom > window.innerHeight * 0.62
    }

    const go = (dir) => {
        if (!dir || snapLock || !onStage()) return
        const next = current + dir
        if (next < 0 || next >= stages.length) return
        playTo(cue(next))
    }

    let wheelBucket = 0
    let wheelReset = 0
    window.addEventListener('wheel', (event) => {
        const dir = Math.sign(event.deltaY)
        if (!dir || !onStage()) return
        event.preventDefault()
        if (snapLock) return
        const scale = event.deltaMode === 1 ? 16 : 1
        wheelBucket += event.deltaY * scale
        window.clearTimeout(wheelReset)
        wheelReset = window.setTimeout(() => { wheelBucket = 0 }, 140)
        if (Math.abs(wheelBucket) < 28) return
        wheelBucket = 0
        go(dir)
    }, { passive: false })

    let touchStartY = null
    window.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1) return
        touchStartY = event.touches[0].clientY
    }, { passive: true })
    window.addEventListener('touchmove', (event) => {
        if (touchStartY == null || event.touches.length !== 1) return
        const dir = Math.sign(touchStartY - event.touches[0].clientY)
        if (!dir || !onStage()) return
        if (event.target.closest('a, button, input, summary')) return
        event.preventDefault()
    }, { passive: false })
    window.addEventListener('touchend', (event) => {
        if (touchStartY == null) return
        const endY = event.changedTouches[0]?.clientY ?? touchStartY
        const delta = touchStartY - endY
        touchStartY = null
        if (Math.abs(delta) < 42) return
        if (event.target.closest('a, button, input, summary')) return
        go(Math.sign(delta))
    }, { passive: true })

    window.addEventListener('keydown', (event) => {
        if (event.altKey || event.ctrlKey || event.metaKey) return
        if (event.target.closest('input, textarea, select, summary')) return
        const dir = event.key === 'ArrowDown' || event.key === 'PageDown' ? 1
            : event.key === 'ArrowUp' || event.key === 'PageUp' ? -1 : 0
        if (!dir || !onStage()) return
        event.preventDefault()
        go(dir)
    })

    window.addEventListener('scroll', () => {
        if (snapLock || onStage()) return
        video.pause()
        if (reverse) reverse.pause()
    }, { passive: true })

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            video.pause()
            if (reverse) reverse.pause()
        }
    })

    mountVideo(true).then(() => {
        note(video.readyState >= 2 ? video.currentTime : 0)
    }).catch(fail)
})()
