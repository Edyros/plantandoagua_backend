(function () {
    document.documentElement.classList.add("js");

    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var header = document.querySelector("[data-header]");
    var toggle = document.querySelector("[data-nav-toggle]");
    var menu = document.querySelector("[data-nav-menu]");

    function onScroll() {
        if (!header) return;
        header.classList.toggle("is-scrolled", window.scrollY > 8);
    }

    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });

    function setMenu(open) {
        if (!menu || !toggle) return;
        menu.classList.toggle("is-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        document.body.classList.toggle("nav-open", open);
        var label = toggle.querySelector(".sr-only");
        if (label) label.textContent = open ? "Fechar menu" : "Abrir menu";
        document.querySelectorAll("main, footer").forEach(function (region) {
            if (open) region.setAttribute("inert", "");
            else region.removeAttribute("inert");
        });
    }

    if (toggle && menu) {
        toggle.addEventListener("click", function () {
            setMenu(!menu.classList.contains("is-open"));
        });
        menu.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () { setMenu(false); });
        });
    }

    function escapeHtml(value) {
        return value.replace(/[&<>"]/g, function (char) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;" }[char];
        });
    }

    function splitWords(el) {
        if (el.dataset.split === "1") return;
        el.dataset.split = "1";
        var text = el.textContent.replace(/\s+/g, " ").trim();
        el.setAttribute("aria-label", text);
        el.innerHTML = text.split(" ").map(function (word, index) {
            var delay = (0.045 * index).toFixed(3);
            return '<span class="stagger-word" aria-hidden="true"><span class="stagger-word-inner" style="animation-delay:' + delay + 's">' + escapeHtml(word) + "</span></span>";
        }).join(" ");
    }

    function mark(selector, className) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.classList.add(className);
        });
    }

    mark(".hero h1, .slide-copy h2, .intro h2, .push-copy h2, .split-copy h2, .statement, .footer h2", "anim-stagger");
    mark(".hero .lede, .hero .btn-line, .slide-copy .kicker, .slide-copy p, .intro .eyebrow, .intro p, .push-copy .eyebrow, .push-copy p, .push-copy .stores, .split-copy .eyebrow, .split-copy p, .card, .moment, .footer > p, .footer .stores", "anim-up");
    mark(".brand, .nav-cta, .nav-toggle", "anim-fade");

    document.querySelectorAll(".anim-stagger").forEach(splitWords);
    document.querySelectorAll(".card").forEach(function (card, index) {
        card.style.animationDelay = (0.08 * index) + "s";
    });
    document.querySelectorAll(".moment").forEach(function (moment, index) {
        moment.style.animationDelay = (0.1 * index) + "s";
    });

    function reveal(el) {
        el.classList.remove("is-in");
        void el.offsetWidth;
        el.classList.add("is-in");
    }

    function revealInside(container) {
        var nodes = [];
        if (container.matches(".anim-stagger, .anim-up, .anim-fade")) nodes.push(container);
        container.querySelectorAll(".anim-stagger, .anim-up, .anim-fade").forEach(function (el) {
            nodes.push(el);
        });
        nodes.forEach(reveal);
    }

    if (reduce) {
        document.querySelectorAll(".anim-stagger, .anim-up, .anim-fade").forEach(function (el) {
            el.classList.add("is-in");
        });
    } else {
        var watcher = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-in");
                watcher.unobserve(entry.target);
            });
        }, { threshold: 0.25 });

        document.querySelectorAll(".anim-stagger, .anim-up, .anim-fade").forEach(function (el) {
            if (el.closest(".slide, .statement, .hero, .story")) return;
            watcher.observe(el);
        });
    }

    var hero = document.querySelector(".hero");
    if (hero) {
        if (reduce) {
            hero.classList.add("is-pumped");
            hero.querySelectorAll(".anim-stagger, .anim-up").forEach(function (el) {
                el.classList.add("is-in");
            });
        } else {
            window.requestAnimationFrame(function () { hero.classList.add("is-pumped"); });
            window.setTimeout(function () {
                hero.querySelectorAll(".anim-stagger, .anim-up").forEach(reveal);
            }, 720);
        }
    }

    document.querySelectorAll("[data-rotator]").forEach(function (root) {
        var items = Array.prototype.slice.call(root.querySelectorAll("[data-item]"));
        var tabs = Array.prototype.slice.call(root.querySelectorAll("[data-tab]"));
        if (!items.length) return;

        var motion = !reduce && root.getAttribute("data-motion") === "slide" ? "slide" : "fade";
        var hoverPause = root.getAttribute("data-hoverpause") !== "off";
        var index = 0;
        var timer = 0;
        var interval = Number(root.getAttribute("data-interval") || 5000);

        if (motion === "slide") root.classList.add("is-slide");

        function sync(current) {
            items.forEach(function (item, itemIndex) {
                var active = itemIndex === current;
                item.classList.toggle("is-active", active);
                item.setAttribute("aria-hidden", active ? "false" : "true");
                if (active) item.removeAttribute("inert");
                else item.setAttribute("inert", "");
            });
            tabs.forEach(function (tab, tabIndex) {
                var active = tabIndex === current;
                tab.classList.toggle("is-active", active);
                tab.setAttribute("aria-selected", active ? "true" : "false");
                tab.tabIndex = active ? 0 : -1;
            });
        }

        function show(next, direction) {
            var target = (next + items.length) % items.length;
            var first = root.dataset.ready !== "1";
            if (!first && target === index) return;
            if (direction == null) direction = 1;

            var outgoing = items[index];
            var incoming = items[target];

            if (motion === "slide" && !first) {
                root.classList.remove("is-ready");
                incoming.classList.remove("is-active", "is-before", "is-after");
                incoming.classList.add(direction > 0 ? "is-after" : "is-before");
                void incoming.offsetWidth;
                root.classList.add("is-ready");
                void incoming.offsetWidth;
                outgoing.classList.remove("is-active", "is-before", "is-after");
                outgoing.classList.add(direction > 0 ? "is-before" : "is-after");
                incoming.classList.remove("is-before", "is-after");
                incoming.classList.add("is-active");
            }

            index = target;
            sync(index);
            if (!reduce) revealInside(incoming);
            root.dataset.ready = "1";
        }

        function stop() {
            window.clearInterval(timer);
            timer = 0;
        }

        function start() {
            if (reduce || items.length < 2) return;
            stop();
            timer = window.setInterval(function () {
                show(index + 1, 1);
            }, interval);
        }

        items.forEach(function (item, itemIndex) {
            item.classList.toggle("is-active", itemIndex === 0);
            item.classList.toggle("is-after", itemIndex !== 0);
            item.classList.remove("is-before");
        });
        sync(0);
        if (!reduce) revealInside(items[0]);
        root.dataset.ready = "1";
        window.requestAnimationFrame(function () {
            root.classList.add("is-ready");
        });

        tabs.forEach(function (tab, tabIndex) {
            tab.addEventListener("click", function () {
                if (tabIndex === index) return;
                show(tabIndex, tabIndex > index ? 1 : -1);
                start();
            });
        });

        var prev = root.querySelector("[data-prev]");
        var next = root.querySelector("[data-next]");
        if (prev) prev.addEventListener("click", function () { show(index - 1, -1); start(); });
        if (next) next.addEventListener("click", function () { show(index + 1, 1); start(); });

        if (hoverPause) {
            root.addEventListener("mouseenter", stop);
            root.addEventListener("mouseleave", start);
        }
        root.addEventListener("focusin", stop);
        root.addEventListener("focusout", function (event) {
            if (!root.contains(event.relatedTarget)) start();
        });
        root.addEventListener("keydown", function (event) {
            if (event.key === "ArrowRight") { show(index + 1, 1); start(); }
            if (event.key === "ArrowLeft") { show(index - 1, -1); start(); }
        });
        document.addEventListener("visibilitychange", function () {
            if (document.hidden) stop();
            else start();
        });

        start();
    });

    var scroller = document.querySelector(".cards");
    var cardPrev = document.querySelector("[data-cards-prev]");
    var cardNext = document.querySelector("[data-cards-next]");

    function stepCards(direction) {
        if (!scroller) return;
        var card = scroller.querySelector(".card");
        if (!card) return;
        var gap = parseFloat(getComputedStyle(scroller).columnGap) || 0;
        var distance = card.getBoundingClientRect().width + gap;
        scroller.scrollBy({ left: distance * direction, behavior: reduce ? "auto" : "smooth" });
    }

    if (cardPrev) cardPrev.addEventListener("click", function () { stepCards(-1); });
    if (cardNext) cardNext.addEventListener("click", function () { stepCards(1); });

    var story = document.querySelector("[data-story]");
    var storyQuery = window.matchMedia("(max-width: 860px)");

    function easeOutBack(t) {
        var c1 = 1.35;
        var c3 = c1 + 1;
        return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function clearBeatMotion(beat) {
        var fill = beat.querySelector(".slot-fill");
        var copy = beat.querySelector(".beat-copy");
        var num = beat.querySelector(".beat-num");
        if (fill) fill.style.transform = "";
        if (copy) {
            copy.style.opacity = "";
            copy.style.transform = "";
        }
        if (num) {
            num.style.opacity = "";
            num.style.transform = "";
        }
    }

    function layoutStory() {
        if (!story) return;
        var viewport = story.querySelector(".story-viewport");
        var track = story.querySelector(".story-track");
        if (!viewport || !track) return;
        var beats = story.querySelectorAll(".beat");
        if (reduce || storyQuery.matches) {
            story.style.height = "";
            track.style.transform = "";
            beats.forEach(function (beat) {
                beat.style.flexBasis = "";
                clearBeatMotion(beat);
            });
            return;
        }
        var width = viewport.clientWidth;
        beats.forEach(function (beat) { beat.style.flexBasis = width + "px"; });
        var distance = Math.max(0, track.scrollWidth - viewport.clientWidth);
        story.style.height = (window.innerHeight + distance) + "px";
    }

    function scrubStory() {
        if (!story || reduce || storyQuery.matches) return;
        var viewport = story.querySelector(".story-viewport");
        var track = story.querySelector(".story-track");
        if (!viewport || !track) return;
        var distance = Math.max(0, track.scrollWidth - viewport.clientWidth);
        var travel = Math.max(1, story.offsetHeight - window.innerHeight);
        var progress = clamp((window.scrollY - story.offsetTop) / travel, 0, 1);
        track.style.transform = "translate3d(" + (-progress * distance).toFixed(2) + "px,0,0)";

        var steps = story.querySelectorAll(".story-steps li");
        var best = -1;
        var active = 0;
        var stepIndex = 0;
        story.querySelectorAll(".beat").forEach(function (beat) {
            if (beat.classList.contains("beat-intro")) return;
            var box = beat.getBoundingClientRect();
            var mid = box.left + box.width * 0.5;
            var delta = (mid - window.innerWidth * 0.5) / window.innerWidth;
            var closeness = 1 - Math.min(1, Math.abs(delta) / 0.9);
            var pumped = easeOutBack(closeness);
            var fill = beat.querySelector(".slot-fill");
            if (fill) {
                var scale = 1.28 - 0.28 * pumped;
                fill.style.transform = "scale(" + scale.toFixed(3) + ")";
            }
            var copy = beat.querySelector(".beat-copy");
            if (copy) {
                var textIn = clamp((closeness - 0.52) / 0.34, 0, 1);
                copy.style.opacity = textIn.toFixed(3);
                copy.style.transform = "translate3d(" + ((1 - textIn) * 72).toFixed(1) + "px," + ((1 - textIn) * 16).toFixed(1) + "px,0)";
            }
            var num = beat.querySelector(".beat-num");
            if (num) {
                num.style.opacity = (0.07 + closeness * 0.16).toFixed(3);
                num.style.transform = "translate3d(" + (delta * -48).toFixed(1) + "px,-50%,0)";
            }
            if (closeness > best) {
                best = closeness;
                active = stepIndex;
            }
            stepIndex += 1;
        });
        steps.forEach(function (step, index) {
            step.classList.toggle("is-on", index === active && best > 0.35);
        });
    }

    if (story && !reduce && storyQuery.matches) {
        var beatWatcher = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                entry.target.classList.toggle("is-hot", entry.isIntersecting && entry.intersectionRatio >= 0.45);
            });
        }, { threshold: [0.45, 0.7] });
        story.querySelectorAll(".beat").forEach(function (beat) { beatWatcher.observe(beat); });
    }

    document.querySelectorAll(".push, .split").forEach(function (panel) {
        panel.classList.add("zoom-panel");
    });
    if (!reduce && "IntersectionObserver" in window) {
        var zoomWatcher = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add("is-hot");
                zoomWatcher.unobserve(entry.target);
            });
        }, { threshold: 0.35 });
        document.querySelectorAll(".zoom-panel").forEach(function (panel) { zoomWatcher.observe(panel); });
    } else {
        document.querySelectorAll(".zoom-panel").forEach(function (panel) { panel.classList.add("is-hot"); });
    }

    var grow = document.querySelector("[data-grow]");
    var growFrame = grow && grow.querySelector(".grow-frame");
    var growCopy = grow && grow.querySelector(".grow-copy");

    function scrubGrow() {
        if (!grow || !growFrame || reduce) return;
        var travel = Math.max(1, grow.offsetHeight - window.innerHeight);
        var progress = clamp((window.scrollY - grow.offsetTop) / travel, 0, 1);
        var pin = grow.querySelector(".grow-pin");
        var narrow = storyQuery.matches;
        var startW = narrow ? 0.7 : 0.3;
        var startH = narrow ? 0.36 : 0.42;
        var maxW = pin ? pin.clientWidth : window.innerWidth;
        var maxH = pin ? pin.clientHeight : window.innerHeight;
        var size = clamp(progress / 0.7, 0, 1);
        var eased = 1 - Math.pow(1 - size, 3);
        growFrame.style.width = (maxW * startW + (maxW - maxW * startW) * eased).toFixed(1) + "px";
        growFrame.style.height = (maxH * startH + (maxH - maxH * startH) * eased).toFixed(1) + "px";
        if (growCopy) {
            var message = clamp((progress - 0.62) / 0.26, 0, 1);
            growCopy.style.opacity = message.toFixed(3);
            growCopy.style.transform = "translate3d(0," + ((1 - message) * 24).toFixed(1) + "px,0)";
        }
    }

    layoutStory();
    scrubStory();
    scrubGrow();
    window.addEventListener("resize", function () {
        layoutStory();
        scrubStory();
        scrubGrow();
    });
    window.addEventListener("scroll", function () {
        scrubStory();
        scrubGrow();
    }, { passive: true });
})();
