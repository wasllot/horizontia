/* Course builder (step "Contenido"): drag & drop of sections/lessons, video picker, article editor and quiz builder.
   Plain globals on purpose: Alpine components are referenced from Blade (x-data="crQuizBuilder(...)") and the file can be
   loaded before or after Alpine starts (full load or wire:navigate). */
(function () {
    'use strict';

    function whenReady(test, run, tries) {
        tries = tries || 0;
        if (test()) { run(); } else if (tries < 80) { setTimeout(function () { whenReady(test, run, tries + 1); }, 50); }
    }

    window.crBuilder = function () { return {}; };

    window.crSortSections = function (el, $wire) {
        whenReady(function () { return window.Sortable; }, function () {
            if (el._crSort) { return; }
            el._crSort = Sortable.create(el, {
                handle: '.cr-handle--sec', draggable: '.cr-sec', animation: 160, ghostClass: 'is-ghost', forceFallback: false,
                onEnd: function () {
                    var ids = Array.prototype.map.call(el.querySelectorAll(':scope > .cr-sec'), function (s) { return +s.dataset.id; });
                    $wire.reorderSections(ids);
                },
            });
        });
    };

    window.crSortLessons = function (el, $wire) {
        whenReady(function () { return window.Sortable; }, function () {
            if (el._crSort) { return; }
            var ids = function (list) { return Array.prototype.map.call(list.querySelectorAll(':scope > .cr-lesson'), function (l) { return +l.dataset.id; }); };
            el._crSort = Sortable.create(el, {
                group: 'cr-lessons', handle: '.cr-handle', draggable: '.cr-lesson', animation: 160, ghostClass: 'is-ghost',
                onEnd: function (evt) {
                    if (evt.to === evt.from) { $wire.reorderLessons(+evt.to.dataset.section, ids(evt.to)); }
                    else { $wire.reorderLessons(+evt.to.dataset.section, ids(evt.to), +evt.from.dataset.section, ids(evt.from)); }
                },
            });
        });
    };

    /* Local preview of a chosen video (blob URL) and its real duration, without uploading anything to read it. */
    window.crVideoPicker = function (existingUrl) {
        return {
            url: existingUrl || null, blob: null, name: '', over: false, uploading: false, progress: 0,
            pick: function (file, input) {
                if (!file) { return; }
                if (input) {   // dropped file: hand it to the real <input> so Livewire uploads it
                    var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    return;
                }
                if (this.blob) { URL.revokeObjectURL(this.blob); }
                this.blob = URL.createObjectURL(file); this.url = this.blob; this.name = file.name;
            },
            duration: function (d) {
                if (this.blob && isFinite(d)) { this.$wire.set('durationSeconds', Math.round(d), false); }
            },
            reset: function () { if (this.blob) { URL.revokeObjectURL(this.blob); } this.blob = null; this.url = existingUrl || null; this.name = ''; },
        };
    };

    window.crArticleEditor = function (initial, uploadUrl) {
        return {
            full: false,
            init: function () {
                var self = this;
                whenReady(function () { return window.jQuery && jQuery.fn.summernote && self.$refs.editor; }, function () {
                    var $ed = jQuery(self.$refs.editor);
                    var cfg = window.articleEditorConfigs('#' + self.$refs.editor.id, uploadUrl, document.querySelector('meta[name="csrf-token"]')?.content);
                    cfg.callbacks = Object.assign(cfg.callbacks || {}, {
                        onChange: function (contents) { self.$wire.set('content.article', contents, false); },
                    });
                    cfg.placeholder = 'Escribe aquí el contenido de la lección…';
                    cfg.height = 380;
                    $ed.summernote(cfg);
                    $ed.summernote('code', initial || '');
                });
            },
            toggle: function () {
                this.full = !this.full;
                document.body.style.overflow = this.full ? 'hidden' : '';
            },
            destroy: function () { document.body.style.overflow = ''; },
        };
    };

    var blankQuestion = function (id) { return { id: id, type: 'single', text: '', options: ['', '', '', ''], correct: 0, explanation: '' }; };

    window.crQuizBuilder = function (initial) {
        var quiz = JSON.parse(JSON.stringify(initial || {}));
        quiz.pass_score = quiz.pass_score == null ? 70 : quiz.pass_score;
        quiz.max_attempts = quiz.max_attempts || 0;
        quiz.show_answers = quiz.show_answers !== false;
        quiz.questions = (quiz.questions || []).map(function (q) {
            if (q.type === 'multiple_choice') { q.type = 'single'; }
            if (q.type === 'multiple' && !Array.isArray(q.correct)) { q.correct = [q.correct || 0]; }
            q.explanation = q.explanation || '';
            return q;
        });
        return {
            quiz: quiz,
            init: function () {
                var self = this;
                this.$watch('quiz', function () { self.sync(); }, { deep: true });
            },
            sync: function () { this.$wire.set('content.quiz', JSON.parse(JSON.stringify(this.quiz)), false); },
            nextId: function () { return this.quiz.questions.reduce(function (m, q) { return Math.max(m, q.id || 0); }, 0) + 1; },
            add: function () { this.quiz.questions.push(blankQuestion(this.nextId())); },
            remove: function (i) { this.quiz.questions.splice(i, 1); },
            duplicate: function (i) { var c = JSON.parse(JSON.stringify(this.quiz.questions[i])); c.id = this.nextId(); this.quiz.questions.splice(i + 1, 0, c); },
            move: function (i, d) {
                var j = i + d; if (j < 0 || j >= this.quiz.questions.length) { return; }
                var q = this.quiz.questions.splice(i, 1)[0]; this.quiz.questions.splice(j, 0, q);
            },
            setType: function (q, t) {
                var wasTF = q.type === 'true_false';
                q.type = t;
                if (t === 'true_false') { q.options = ['Verdadero', 'Falso']; q.correct = 0; return; }
                if (wasTF) { q.options = ['', '', '', '']; }
                q.correct = t === 'multiple' ? (Array.isArray(q.correct) ? q.correct : [q.correct || 0]) : (Array.isArray(q.correct) ? (q.correct[0] || 0) : (q.correct || 0));
            },
            isCorrect: function (q, oi) { return q.type === 'multiple' ? (q.correct || []).indexOf(oi) !== -1 : q.correct === oi; },
            toggleCorrect: function (q, oi) {
                if (q.type !== 'multiple') { q.correct = oi; return; }
                var set = (q.correct || []).slice(), at = set.indexOf(oi);
                if (at === -1) { set.push(oi); } else { set.splice(at, 1); }
                q.correct = set.sort(function (a, b) { return a - b; });
            },
            addOption: function (q) { if (q.options.length < 8) { q.options.push(''); } },
            removeOption: function (q, oi) {
                q.options.splice(oi, 1);
                if (q.type === 'multiple') {
                    q.correct = (q.correct || []).filter(function (c) { return c !== oi; }).map(function (c) { return c > oi ? c - 1 : c; });
                } else if (q.correct === oi) { q.correct = 0; } else if (q.correct > oi) { q.correct -= 1; }
            },
        };
    };
})();
