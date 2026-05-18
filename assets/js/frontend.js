(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('uesp-live-root');
        if (!root || typeof UESP_DATA === 'undefined') {
            return;
        }

        const timerEl = document.getElementById('uesp-timer');
        const questionBox = document.getElementById('uesp-question');
        const paletteBox = document.getElementById('uesp-palette');
        const submitBtn = document.getElementById('uesp-submit-btn');
        const categorySelect = document.getElementById('uesp-category-filter');
        const statusEl = document.getElementById('uesp-status');

        if (!timerEl || !questionBox || !paletteBox || !submitBtn) {
            return;
        }

        let questions = [];
        let answers = {};
        let current = 0;
        let remaining = parseInt(UESP_DATA.duration, 10) || 1800;
        let timer = null;
        let submitted = false;
        let loading = false;
        const clientId = getClientId();

        window.onbeforeunload = null;

        function i18n(key, fallback) {
            return UESP_DATA.i18n && UESP_DATA.i18n[key] ? UESP_DATA.i18n[key] : fallback;
        }

        function getClientId() {
            const key = 'uesp_client_id';
            let existing = window.localStorage ? window.localStorage.getItem(key) : '';
            if (!existing) {
                existing = 'uesp_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
                if (window.localStorage) {
                    window.localStorage.setItem(key, existing);
                }
            }
            return existing;
        }

        function setStatus(message, type) {
            statusEl.textContent = message || '';
            statusEl.className = 'uesp-status' + (type ? ' is-' + type : '');
        }

        function post(action, data) {
            const body = new URLSearchParams(Object.assign({
                action: action,
                nonce: UESP_DATA.nonce,
                client_id: clientId
            }, data || {}));

            return fetch(UESP_DATA.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body
            }).then(function (response) {
                return response.json().then(function (json) {
                    if (!response.ok) {
                        throw json;
                    }
                    return json;
                });
            });
        }

        function startTimer() {
            stopTimer();
            updateTimer();
            timer = window.setInterval(function () {
                remaining -= 1;
                updateTimer();

                if (remaining <= 0) {
                    submitExam(true);
                }
            }, 1000);
        }

        function stopTimer() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function updateTimer() {
            const safeTime = Math.max(remaining, 0);
            const minutes = String(Math.floor(safeTime / 60)).padStart(2, '0');
            const seconds = String(safeTime % 60).padStart(2, '0');
            timerEl.textContent = minutes + ':' + seconds;
            timerEl.classList.toggle('is-warning', safeTime <= 300);
        }

        function loadExam(category) {
            if (loading || submitted) {
                return;
            }

            loading = true;
            answers = {};
            current = 0;
            remaining = parseInt(UESP_DATA.duration, 10) || 1800;
            submitBtn.disabled = true;
            questionBox.innerHTML = '<div class="uesp-loading">' + escapeHtml(i18n('loading', 'Loading questions...')) + '</div>';
            paletteBox.innerHTML = '';
            setStatus('', '');

            post('uesp_load_exam', { category: category || '' })
                .then(function (res) {
                    questions = Array.isArray(res.questions) ? res.questions : [];
                    loading = false;
                    submitBtn.disabled = questions.length === 0;
                    render();
                    if (questions.length) {
                        startTimer();
                    } else {
                        stopTimer();
                        updateTimer();
                    }
                })
                .catch(function () {
                    loading = false;
                    submitBtn.disabled = false;
                    questionBox.innerHTML = '<div class="uesp-empty"><h3>' + escapeHtml(i18n('submitFailed', 'Unable to load the exam. Please try again.')) + '</h3></div>';
                    stopTimer();
                });
        }

        function render() {
            renderPalette();

            if (!questions.length) {
                questionBox.innerHTML = '<div class="uesp-empty"><h3>' + escapeHtml(i18n('noQuestions', 'No questions found for this category.')) + '</h3></div>';
                return;
            }

            const question = questions[current];
            const selected = answers[String(question.id)] || '';
            const fragment = document.createDocumentFragment();
            const item = document.createElement('div');
            item.className = 'uesp-question-item active';

            const top = document.createElement('div');
            top.className = 'uesp-question-top';
            top.innerHTML = '<span class="uesp-badge"></span><span class="uesp-mark-badge"></span>';
            top.querySelector('.uesp-badge').textContent = i18n('question', 'Question') + ' ' + (current + 1) + ' ' + i18n('of', 'of') + ' ' + questions.length;
            top.querySelector('.uesp-mark-badge').textContent = question.marks + ' ' + i18n('mark', 'Mark');
            item.appendChild(top);

            const text = document.createElement('div');
            text.className = 'uesp-question-text';
            text.textContent = stripHtml(question.question_text || '');
            item.appendChild(text);

            const options = document.createElement('div');
            options.className = 'uesp-options';

            (question.options || []).forEach(function (option, index) {
                const label = document.createElement('label');
                label.className = 'uesp-option';
                if (selected === String(option.option_key)) {
                    label.classList.add('is-selected');
                }

                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'question_' + question.id;
                input.value = String(option.option_key);
                input.checked = selected === String(option.option_key);
                input.addEventListener('change', function () {
                    answers[String(question.id)] = this.value;
                    saveAnswer(question.id, this.value);
                    renderPalette();
                    document.querySelectorAll('.uesp-option').forEach(function (node) {
                        node.classList.remove('is-selected');
                    });
                    label.classList.add('is-selected');
                });

                const ui = document.createElement('span');
                ui.className = 'uesp-option-ui';

                const letter = document.createElement('span');
                letter.className = 'uesp-option-letter';
                letter.textContent = String.fromCharCode(65 + index);

                const optionText = document.createElement('span');
                optionText.className = 'uesp-option-text';
                optionText.textContent = option.option_text || '';

                ui.appendChild(letter);
                ui.appendChild(optionText);
                label.appendChild(input);
                label.appendChild(ui);
                options.appendChild(label);
            });

            item.appendChild(options);

            const actions = document.createElement('div');
            actions.className = 'uesp-question-actions';

            const prev = document.createElement('button');
            prev.type = 'button';
            prev.className = 'uesp-btn uesp-btn-glass';
            prev.textContent = '← ' + i18n('previous', 'Previous');
            prev.disabled = current === 0;
            prev.addEventListener('click', function () { goTo(current - 1); });

            const next = document.createElement('button');
            next.type = 'button';
            next.className = current === questions.length - 1 ? 'uesp-btn uesp-btn-danger' : 'uesp-btn uesp-btn-gradient';
            next.textContent = current === questions.length - 1 ? i18n('finish', 'Finish Exam') : i18n('next', 'Next') + ' →';
            next.addEventListener('click', function () {
                if (current === questions.length - 1) {
                    submitExam(false);
                } else {
                    goTo(current + 1);
                }
            });

            actions.appendChild(prev);
            actions.appendChild(next);
            item.appendChild(actions);
            fragment.appendChild(item);

            questionBox.innerHTML = '';
            questionBox.appendChild(fragment);
        }

        function renderPalette() {
            paletteBox.innerHTML = '';
            questions.forEach(function (question, index) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'uesp-palette-btn';
                button.textContent = String(index + 1);
                button.dataset.question = String(index);
                button.setAttribute('aria-label', i18n('question', 'Question') + ' ' + (index + 1));

                if (index === current) {
                    button.classList.add('is-current');
                }
                if (answers[String(question.id)]) {
                    button.classList.add('is-answered');
                    button.title = i18n('answered', 'Answered');
                } else {
                    button.classList.add('is-unanswered');
                    button.title = i18n('unanswered', 'Unanswered');
                }

                button.addEventListener('click', function () { goTo(index); });
                paletteBox.appendChild(button);
            });
        }

        function goTo(index) {
            if (index < 0 || index >= questions.length || submitted) {
                return;
            }
            current = index;
            render();
        }

        function saveAnswer(questionId, answer) {
            post('uesp_save_answer', {
                question_id: questionId,
                answer: answer
            }).catch(function () {
                // The final submit still sends the full local answer object.
            });
        }

        function submitExam(isAutoSubmit) {
            if (submitted) {
                return;
            }

            if (!isAutoSubmit && !window.confirm(i18n('confirmSubmit', 'Are you sure you want to submit your exam?'))) {
                return;
            }

            submitted = true;
            stopTimer();
            window.onbeforeunload = null;
            submitBtn.disabled = true;
            submitBtn.textContent = i18n('submitting', 'Submitting...');
            setStatus(i18n('submitting', 'Submitting...'), 'loading');

            post('uesp_submit_exam', {
                answers: JSON.stringify(buildSubmissionAnswers())
            }).then(function (res) {
                const attemptId = res.attempt_id || (res.data && res.data.attempt_id);
                const redirectUrl = res.redirect_url || (res.data && res.data.redirect_url) || (UESP_DATA.resultUrl + '?attempt_id=' + encodeURIComponent(attemptId));
                window.location.href = redirectUrl;
            }).catch(function (error) {
                submitted = false;
                submitBtn.disabled = false;
                submitBtn.textContent = i18n('submit', 'Submit Exam');
                setStatus((error && error.data && error.data.message) ? error.data.message : i18n('submitFailed', 'Unable to submit the exam. Please try again.'), 'error');
                startTimer();
            });
        }

        function buildSubmissionAnswers() {
            const payload = {};
            questions.forEach(function (question) {
                payload[String(question.id)] = answers[String(question.id)] || '';
            });
            return payload;
        }

        function stripHtml(value) {
            const div = document.createElement('div');
            div.innerHTML = value;
            return div.textContent || div.innerText || '';
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>'"]/g, function (char) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '\'': '&#039;', '"': '&quot;' })[char];
            });
        }

        submitBtn.addEventListener('click', function () { submitExam(false); });

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                stopTimer();
                loadExam(this.value);
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden && !submitted) {
                post('uesp_log_suspicious_event', { event_type: 'tab_hidden' }).catch(function () {});
            }
        });

        loadExam(categorySelect ? categorySelect.value : '');
    });
}());
