/* Lesson player (student): test taking and live-session countdown. Plain globals so Blade can reference them from x-data. */
(function () {
    'use strict';

    /* ---------- Test ---------- */
    window.crQuiz = function (quiz, state, nextLessonId) {
        state = state || {};
        return {
            quiz: quiz, nextLessonId: nextLessonId,
            mode: 'intro', answers: {}, loading: false, error: '', result: { correct: 0, total: 0, results: {} },
            score: state.score === undefined ? null : state.score, passed: !!state.passed, attempts: state.attempts || 0,
            get answered() { var self = this; return this.quiz.questions.filter(function (q) { var a = self.answers[q.id]; return Array.isArray(a) ? a.length > 0 : a !== undefined && a !== null; }).length; },
            get canRetry() { return !this.quiz.max_attempts || this.attempts < this.quiz.max_attempts; },
            get review() { return !!(this.result && this.result.results && Object.keys(this.result.results).length && Object.values(this.result.results).some(function (r) { return r.correct_answer !== undefined; })); },
            start: function () { this.answers = {}; this.result = { correct: 0, total: 0, results: {} }; this.error = ''; this.mode = 'taking'; },
            isOn: function (q, oi) { var a = this.answers[q.id]; return q.multiple ? (a || []).indexOf(oi) !== -1 : a === oi; },
            choose: function (q, oi) {
                if (this.mode !== 'taking') { return; }
                if (q.multiple) {
                    var set = (this.answers[q.id] || []).slice(), at = set.indexOf(oi);
                    if (at === -1) { set.push(oi); } else { set.splice(at, 1); }
                    this.answers[q.id] = set.sort(function (a, b) { return a - b; });
                } else { this.answers[q.id] = oi; }
            },
            /* After grading: green for a correct option, red for a wrong pick. */
            reviewState: function (q, oi) {
                if (this.mode !== 'result' || !this.result || !this.result.results) { return ''; }
                var r = this.result.results[q.id];
                if (!r || r.correct_answer === undefined) { return ''; }
                var good = Array.isArray(r.correct_answer) ? r.correct_answer.indexOf(oi) !== -1 : r.correct_answer === oi;
                if (good) { return 'ok'; }
                return this.isOn(q, oi) ? 'bad' : '';
            },
            submit: function () {
                var self = this;
                if (this.loading || this.answered < this.quiz.questions.length) { return; }
                this.loading = true; this.error = '';
                this.$wire.submitQuiz(JSON.parse(JSON.stringify(this.answers))).then(function (res) {
                    self.loading = false;
                    if (!res || res.error) { self.error = res && res.error === 'attempts' ? 'Ya usaste todos los intentos permitidos.' : 'No se pudo enviar el test. Inténtalo de nuevo.'; return; }
                    self.result = res; self.score = res.score; self.passed = res.passed; self.attempts = res.attempts; self.mode = 'result';
                }).catch(function () { self.loading = false; self.error = 'No se pudo enviar el test. Revisa tu conexión.'; });
            },
        };
    };

    /* ---------- Sesión en vivo ---------- */
    window.crLive = function (live) {
        var t = function (iso) { return iso ? new Date(iso).getTime() : 0; };
        return {
            live: live, now: Date.now(), phase: live ? live.phase : 'upcoming', joinUrl: live ? live.join_url : null, recording: live ? live.recording_url : null, timer: null, fetched: {},
            init: function () {
                if (!this.live) { return; }
                var self = this;
                this.tick();
                this.timer = setInterval(function () { self.tick(); }, 1000);
            },
            destroy: function () { if (this.timer) { clearInterval(this.timer); } },
            tick: function () {
                this.now = Date.now();
                var start = t(this.live.starts_at), end = t(this.live.ends_at), from = t(this.live.join_from), phase;
                if (this.now >= end) { phase = 'ended'; } else if (this.now >= start) { phase = 'live'; } else if (this.now >= from) { phase = 'soon'; } else { phase = 'upcoming'; }
                if (phase !== this.phase) {
                    this.phase = phase;
                    this.refresh();
                }
            },
            /* The server hands out the meeting link / recording only in the right phase: ask for the updated state. */
            refresh: function () {
                var self = this;
                this.$wire.refreshLive().then(function () {
                    return self.$wire.$get('activeCurriculum.live');
                }).then(function (l) {
                    if (l) { self.live = l; self.joinUrl = l.join_url; self.recording = l.recording_url; }
                });
            },
            get label() { return { upcoming: 'Próximamente', soon: 'Por comenzar', live: 'En vivo ahora', ended: 'Finalizada', cancelled: 'Cancelada' }[this.phase] || ''; },
            get units() {
                var ms = Math.max(0, t(this.live.starts_at) - this.now), s = Math.floor(ms / 1000);
                var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600), m = Math.floor(s % 3600 / 60), sec = s % 60;
                var pad = function (n) { return String(n).padStart(2, '0'); };
                return [{ k: 'd', v: d, l: 'días' }, { k: 'h', v: pad(h), l: 'horas' }, { k: 'm', v: pad(m), l: 'min' }, { k: 's', v: pad(sec), l: 'seg' }];
            },
        };
    };

    /* ---------- Video: retomar donde te quedaste y saltar a un minuto ---------- */
    window.crLessonVideo = {
        player: function () {
            if (!window.videojs || !videojs.getPlayers) { return null; }
            var all = videojs.getPlayers(), k;
            for (k in all) { if (all[k] && !all[k].isDisposed_ && /^video-\d+$/.test(k)) { return all[k]; } }
            return null;
        },
        time: function () { var p = this.player(); return p && p.readyState() > 0 ? Math.floor(p.currentTime()) : null; },
        seek: function (s) { var p = this.player(); if (p) { p.currentTime(s); p.play(); window.scrollTo({ top: 0, behavior: 'smooth' }); } },
        /* Guarda la posición cada 10 s y al pausar/salir; al cargar salta a donde se quedó. */
        track: function (player, curriculumId, url, csrf, resumeAt) {
            var last = -100, busy = false;
            var save = function (force) {
                var t = Math.floor(player.currentTime() || 0);
                if (busy || (!force && Math.abs(t - last) < 10)) { return; }
                last = t; busy = true;
                fetch(url, { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ curriculum_id: curriculumId, seconds: t }) })
                    .catch(function () {}).then(function () { busy = false; });
            };
            if (resumeAt > 0) {
                player.one('loadedmetadata', function () {
                    player.currentTime(resumeAt);
                    var m = Math.floor(resumeAt / 60), sec = ('0' + (resumeAt % 60)).slice(-2);
                    if (typeof showAlert === 'function') { showAlert({ type: 'info', title: 'Retomamos donde te quedaste', message: 'Minuto ' + m + ':' + sec, autoclose: 3500 }); }
                });
            }
            player.on('timeupdate', function () { save(false); });
            player.on('pause', function () { save(true); });
            player.on('ended', function () { last = -100; busy = false; save(true); });
            document.addEventListener('visibilitychange', function () { if (document.hidden) { save(true); } });
        }
    };
})();
