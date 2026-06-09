import './bootstrap';
// import './runtime-overrides';
import * as bootstrap from 'bootstrap';
import 'admin-lte/dist/js/adminlte.js';

import { OverlayScrollbars } from 'overlayscrollbars';

document.addEventListener('DOMContentLoaded', () => {
    const runtime = window.Ba3eedOverrides || {};
    const sidebarWrapper = document.querySelector('.sidebar-wrapper');

    if (sidebarWrapper && window.innerWidth >= 992) {
        OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
                autoHide: 'leave',
                autoHideDelay: 600,
            },
        });
    }

    initializeThemeToggle();
    initializeGlobalModals();
    initializePageToasts();
    initializeTeacherChart();
    initializeStudentChart();
    initializePhoneInputs();
    initializeBookingModeForms();
    initializeTeacherLiveAvailabilitySwitches();
    initializeTeacherLivePresence();
    initializeDeviceCheckForms();
    initializeJoinSessionLinks();
    initializeTeacherSlotPicker();
    initializeTeacherSubjectPricing();
    initializeBookingBalanceChecks();
    initializeWalletForms();
    (runtime.initializeTeacherSessionPanel || initializeTeacherSessionPanel)();
    (runtime.initializeAdminRealtime || initializeAdminRealtime)();
    (runtime.initializeStudentSessionPanel || initializeStudentSessionPanel)();
    (runtime.initializeTeacherLivePolling || initializeTeacherLivePolling)();
    (runtime.initializeTeacherRealtime || initializeTeacherRealtime)();
    (runtime.initializeStudentRealtime || initializeStudentRealtime)();
    (runtime.initializeJoinSessionPrompts || initializeJoinSessionPrompts)();
    (runtime.initializeBookingResult || initializeBookingResult)();
    initializeVideoCallPage();
    (runtime.initializeLiveSessionRoom || initializeLiveSessionRoom)();
});

function initializeGlobalModals() {
    const modalIds = [
        'studentBookingModal',
        'studentWalletDepositModal',
        'studentWalletWithdrawModal',
        'teacherWalletWithdrawModal',
        'studentCancelSessionModal',
        'teacherJoinSessionPrompt',
        'studentJoinSessionPrompt',
        'liveSessionEndConfirmModal',
    ];

    modalIds.forEach(modalId => {
        const modal = document.getElementById(modalId);

        if (!modal || modal.parentElement === document.body) {
            return;
        }

        document.body.appendChild(modal);
    });
}

function initializeThemeToggle() {
    const toggles = document.querySelectorAll('[data-theme-toggle]');

    if (!toggles.length) {
        return;
    }

    const scope = document.body.dataset.themeScope || 'app';
    const storageKey = `ba3eed-theme-${scope}`;
    const initialTheme = localStorage.getItem(storageKey) || 'light';

    const applyTheme = theme => {
        document.documentElement.setAttribute('data-theme', theme);
        toggles.forEach(toggle => {
            const label = toggle.querySelector('span');

            if (label) {
                label.textContent = theme === 'dark' ? 'الوضع الليلي' : 'الوضع النهاري';
            }
        });
    };

    applyTheme(initialTheme);

    toggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem(storageKey, nextTheme);
            applyTheme(nextTheme);
        });
    });
}

function initializePhoneInputs() {
    const inputs = document.querySelectorAll('[data-phone-input]');

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            let value = input.value.replace(/[^\d+]/g, '');

            if (value.includes('+')) {
                value = `${value.startsWith('+') ? '+' : ''}${value.replace(/\+/g, '')}`;
            }

            input.value = value;
        });
    });
}

function safeJsonParse(value, fallback = {}) {
    if (!value) {
        return fallback;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

async function findMediaInputs() {
    if (!navigator.mediaDevices || typeof navigator.mediaDevices.enumerateDevices !== 'function') {
        return { supported: false, hasAudio: false, hasVideo: false };
    }

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();

        return {
            supported: true,
            hasAudio: devices.some(device => device.kind === 'audioinput'),
            hasVideo: devices.some(device => device.kind === 'videoinput'),
        };
    } catch (error) {
        return { supported: false, hasAudio: false, hasVideo: false };
    }
}

function getMediaMissingMessage(status, actionName = 'هذا الإجراء') {
    if (!status.supported) {
        return `لا يمكن التحقق من وجود الكاميرا أو الميكروفون على هذا المتصفح. حاول استخدام متصفح حديث أو التأكد من ضبط الإعدادات.`;
    }

    if (!status.hasAudio && !status.hasVideo) {
        return `لا يوجد مايك أو كاميرا متاحين. استخدم جهازاً بميكروفون وكاميرا قبل متابعة ${actionName}.`;
    }

    if (!status.hasAudio) {
        return `لا يوجد مايك متاح. استخدم جهازاً يحتوي على مايك قبل متابعة ${actionName}.`;
    }

    if (!status.hasVideo) {
        return `لا توجد كاميرا متاحة. استخدم جهازاً يحتوي على كاميرا قبل متابعة ${actionName}.`;
    }

    return null;
}

async function ensureMediaInputsForAction(actionName = 'هذا الإجراء') {
    const status = await findMediaInputs();
    const message = getMediaMissingMessage(status, actionName);

    if (message) {
        showAppToast(message, 'danger');
        return false;
    }

    return true;
}

function initializeTeacherSubjectPricing() {
    document.querySelectorAll('[data-teacher-subject-pricing]').forEach(form => {
        const input = form.querySelector('[data-hourly-rate-input]');
        const netPreview = form.querySelector('[data-teacher-net-preview]');
        const adminPreview = form.querySelector('[data-admin-share-preview]');
        const commission = Number(form.dataset.adminCommission || 0);
        const formatAmount = value => `${Math.max(0, value).toLocaleString('ar-SY', { maximumFractionDigits: 0 })} ل.س`;

        const render = () => {
            const gross = Number(input?.value || 0);
            const adminShare = gross * (commission / 100);
            const net = gross - adminShare;

            if (netPreview) {
                netPreview.textContent = formatAmount(net);
            }

            if (adminPreview) {
                adminPreview.textContent = formatAmount(adminShare);
            }
        };

        input?.addEventListener('input', render);
        render();
    });
}

function ensureBookingConfirmModal() {
    let modal = document.getElementById('studentBookingConfirmModal');

    if (modal) {
        return modal;
    }

    modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'studentBookingConfirmModal';
    modal.tabIndex = -1;
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0" data-booking-confirm-title>تأكيد الحجز</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="mb-3" data-booking-confirm-message></p>
                    <div class="student-list-card mb-4">
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">الأستاذ</span>
                                <strong data-booking-confirm-teacher></strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">سعر الساعة</span>
                                <strong data-booking-confirm-rate></strong>
                            </div>
                            <div class="d-flex justify-content-between gap-3">
                                <span class="text-muted">إجمالي التكلفة</span>
                                <strong data-booking-confirm-total></strong>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn student-btn-soft" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn student-btn-primary" data-booking-confirm-submit>تأكيد الحجز</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    return modal;
}

function showBookingConfirmModal(preview, onConfirm) {
    const modalElement = ensureBookingConfirmModal();
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const formatAmount = value => `${Number(value || 0).toLocaleString('ar-SY', { maximumFractionDigits: 0 })} ل.س`;
    const modeLabel = preview.mode === 'instant' ? 'تم إيجاد أستاذ متاح للحجز المباشر.' : 'سيتم حجز الموعد حسب الاختيار.';

    modalElement.querySelector('[data-booking-confirm-message]').textContent = `${modeLabel} تكلفة الساعة ${formatAmount(preview.hourly_rate)}.`;
    modalElement.querySelector('[data-booking-confirm-teacher]').textContent = preview.teacher_name || 'أستاذ متاح';
    modalElement.querySelector('[data-booking-confirm-rate]').textContent = formatAmount(preview.hourly_rate);
    modalElement.querySelector('[data-booking-confirm-total]').textContent = formatAmount(preview.total);

    const submitButton = modalElement.querySelector('[data-booking-confirm-submit]');
    const nextSubmitButton = submitButton.cloneNode(true);
    submitButton.replaceWith(nextSubmitButton);
    nextSubmitButton.addEventListener('click', () => {
        modal.hide();
        onConfirm();
    });

    modal.show();
}

function syncPreviewTeacher(form, preview) {
    if (!preview.teacher_id) {
        return;
    }

    let teacherInput = form.querySelector('input[name="teacher_id"]');

    if (!teacherInput) {
        teacherInput = document.createElement('input');
        teacherInput.type = 'hidden';
        teacherInput.name = 'teacher_id';
        form.appendChild(teacherInput);
    }

    teacherInput.value = String(preview.teacher_id);
}

async function submitConfirmedForm(form) {
    const submitUrl = form.action;
    const submitButton = form.querySelector('button[type="submit"]');

    if (!submitUrl || !window.fetch) {
        form.dataset.bookingCostConfirmHandled = '1';

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
        return;
    }

    submitButton?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(submitUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: new FormData(form),
        });

        const payload = await response.json().catch(() => null);

        if (response.ok && payload?.status === 'ok') {
            if (payload.redirect_url) {
                window.location.assign(payload.redirect_url);
                return;
            }

            showAppToast(payload.message || 'تم الحجز بنجاح.', 'success');

            // Close booking modal if open
            const bookingModalEl = document.getElementById('studentBookingModal');
            if (bookingModalEl) {
                bootstrap.Modal.getOrCreateInstance(bookingModalEl).hide();
            }

            if (payload.popup) {
                const prompt = document.getElementById('studentJoinSessionPrompt');

                if (prompt) {
                    applyJoinPromptPayload(prompt, payload.popup, { forceShow: true });
                }
            }

            // Optionally refresh parts of the page or poll state
            return;
        }

        const msg = payload?.message || (payload?.errors ? Object.values(payload.errors).flat()[0] : 'تعذر إتمام الحجز.');
        showAppToast(msg, 'danger');
    } catch (error) {
        // Fallback to normal submit if network/error
        try {
            form.dataset.bookingCostConfirmHandled = '1';

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        } catch (e) {
            showAppToast('تعذر إتمام الحجز. حاول مجددا.', 'danger');
        }
    } finally {
        submitButton?.removeAttribute('disabled');
    }
}

async function requestBookingConfirmation(form, fallbackPreview = null) {
    const previewUrl = form.dataset.bookingPreviewUrl;

    if (!previewUrl) {
        if (fallbackPreview) {
            showBookingConfirmModal(fallbackPreview, () => {
                submitConfirmedForm(form);
            });
        }

        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(previewUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: new FormData(form),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const errors = payload?.errors ? Object.values(payload.errors).flat() : [];
            const serverMessage = payload?.message || errors[0];

            if (serverMessage) {
                showAppToast(serverMessage, 'danger');
                return;
            }

            // If no JSON message is present, try to show a fallback with HTTP status
            showAppToast(`تعذر تجهيز الحجز الآن. (${response.status})`, 'danger');
            return;
        }

        if (!payload.can_afford) {
            showAppToast(`رصيدك الحالي لا يكفي. المطلوب ${Math.round(payload.total || 0)} والمتوفر ${Math.round(payload.balance || 0)}.`, 'danger');
            return;
        }

        showBookingConfirmModal(payload, () => {
            syncPreviewTeacher(form, payload);
            submitConfirmedForm(form);
        });
    } catch (error) {
        showAppToast('تعذر تجهيز الحجز الآن. حاول مجددا.', 'danger');
    } finally {
        submitButton?.removeAttribute('disabled');
    }
}

function ensureToastRoot() {
    let root = document.querySelector('[data-app-toast-root]');

    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.setAttribute('data-app-toast-root', '1');
    root.className = 'app-toast-root';
    document.body.appendChild(root);

    return root;
}

function showAppToast(message, type = 'danger', duration = 3500) {
    if (!message) {
        return;
    }

    const root = ensureToastRoot();
    const toast = document.createElement('div');
    toast.className = `app-toast app-toast-${type}`;
    toast.textContent = message;
    root.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    window.setTimeout(() => {
        toast.classList.remove('is-visible');
        window.setTimeout(() => toast.remove(), 220);
    }, duration);
}

function initializePageToasts() {
    document.querySelectorAll('[data-page-toast]').forEach(node => {
        const message = node.dataset.toastMessage;
        const type = node.dataset.toastType || 'danger';

        if (message) {
            showAppToast(message, type);
        }
    });
}

function sessionEndTimestamp(payload) {
    const explicitEnd = payload?.planned_end_at_iso || payload?.planned_end_at;
    const durationHours = Number(payload?.duration_hours || 1);

    if (explicitEnd) {
        const explicitEndTimestamp = new Date(explicitEnd).getTime();

        return Number.isNaN(explicitEndTimestamp) ? null : explicitEndTimestamp;
    }

    const base = payload?.started_at_iso || payload?.scheduled_at_iso;

    if (!base) {
        return null;
    }

    return new Date(base).getTime() + (durationHours * 60 * 60 * 1000);
}

function payloadCanJoinNow(payload) {
    if (!payload) {
        return false;
    }

    if (payload.can_join_now) {
        return true;
    }

    if (payload.status === 'cancelled' || payload.status === 'completed') {
        return false;
    }

    if (Object.hasOwn(payload, 'teacher_confirmed') && !payload.teacher_confirmed) {
        return false;
    }

    if (Object.hasOwn(payload, 'student_confirmed') && !payload.student_confirmed) {
        return false;
    }

    if (!payload.scheduled_at_iso) {
        return false;
    }

    const now = Date.now();
    const scheduledAt = new Date(payload.scheduled_at_iso).getTime();

    if (Number.isNaN(scheduledAt) || now < scheduledAt) {
        return false;
    }

    if (!payload.started_at_iso && payload.join_deadline_at_iso) {
        const joinDeadline = new Date(payload.join_deadline_at_iso).getTime();

        if (!Number.isNaN(joinDeadline) && now >= joinDeadline) {
            return false;
        }
    }

    const endAt = sessionEndTimestamp(payload);

    return endAt ? now < endAt : Boolean(payload.can_join_now);
}

function normalizeJoinPayload(payload) {
    if (!payload) {
        return null;
    }

    return {
        ...payload,
        can_join_now: payloadCanJoinNow(payload),
    };
}

function joinPayloadFromSessionUpdate(scope, payload) {
    if (!payload?.id || !payload?.join_url) {
        return null;
    }

    return {
        id: payload.id,
        subject_name: payload.subject_name || 'جلسة',
        participant_name: scope === 'teacher'
            ? (payload.student_name || 'الطالب')
            : (payload.teacher_name || 'الأستاذ'),
        scheduled_at_label: payload.scheduled_at || '',
        scheduled_at_iso: payload.scheduled_at_iso || null,
        started_at_iso: payload.started_at_iso || null,
        duration_hours: Number(payload.duration_hours || 1),
        status: payload.status || 'upcoming',
        can_join_now: Boolean(payload.can_join_now),
        join_url: payload.join_url,
    };
}

function joinPromptStorageKey(payload) {
    return `join-prompt-shown-${document.body.dataset.themeScope || 'app'}-${payload.id}`;
}

function applyJoinPromptPayload(prompt, payload, options = {}) {
    if (!prompt) {
        return;
    }

    const normalized = normalizeJoinPayload(payload);
    const forceShow = Boolean(options.forceShow);
    const summary = prompt.querySelector('[data-join-session-summary]');
    const time = prompt.querySelector('[data-join-session-time]');
    const link = prompt.querySelector('[data-join-session-link]');
    const modal = bootstrap.Modal.getOrCreateInstance(prompt);

    window.clearTimeout(prompt._joinPromptTimer);
    prompt.dataset.joinSessionModal = JSON.stringify(normalized || null);

    if (summary) {
        summary.textContent = normalized
            ? `${normalized.subject_name || 'جلسة'} مع ${normalized.participant_name || 'المشارك'}`
            : 'لا توجد جلسة جاهزة الآن';
    }

    if (time) {
        time.textContent = normalized?.scheduled_at_label || '';
    }

    if (link) {
        link.href = normalized?.join_url || '#';
        link.classList.toggle('disabled', !normalized?.join_url);
    }

    if (!normalized) {
        if (prompt.classList.contains('show')) {
            modal.hide();
        }

        return;
    }

    const showPrompt = () => {
        const storageKey = joinPromptStorageKey(normalized);

        if (!forceShow && sessionStorage.getItem(storageKey)) {
            return;
        }

        sessionStorage.setItem(storageKey, '1');
        modal.show();
    };

    if (normalized.can_join_now) {
        window.setTimeout(showPrompt, 250);
        return;
    }

    if (!normalized.scheduled_at_iso) {
        return;
    }

    const delay = new Date(normalized.scheduled_at_iso).getTime() - Date.now();

    if (delay <= 0) {
        return;
    }

    prompt._joinPromptTimer = window.setTimeout(() => {
        const refreshedPayload = normalizeJoinPayload(safeJsonParse(prompt.dataset.joinSessionModal, null));
        applyJoinPromptPayload(prompt, refreshedPayload);
    }, Math.min(delay, 2147483647));
}

function updateSessionTriggerPayload(selector, sessionPayload) {
    const triggers = document.querySelectorAll(selector);

    if (!triggers.length || !sessionPayload?.id) {
        return;
    }

    triggers.forEach(trigger => {
        const currentPayload = safeJsonParse(trigger.dataset.session, null);

        if (!currentPayload || Number(currentPayload.id) !== Number(sessionPayload.id)) {
            return;
        }

        trigger.dataset.session = JSON.stringify({
            ...currentPayload,
            ...sessionPayload,
        });

        if (trigger.classList.contains('active')) {
            trigger.click();
        }
    });
}

function renderQuickJoinTargets(scope, payload) {
    const normalized = normalizeJoinPayload(payload);
    const cards = document.querySelectorAll(`[data-quick-join-card="${scope}"]`);
    const summaries = document.querySelectorAll(`[data-quick-join-summary="${scope}"]`);
    const times = document.querySelectorAll(`[data-quick-join-time="${scope}"]`);
    const buttons = document.querySelectorAll(`[data-quick-join-button="${scope}"]`);

    cards.forEach(card => {
        card.classList.toggle('d-none', !normalized);
    });

    summaries.forEach(summary => {
        summary.textContent = normalized
            ? `${normalized.subject_name || 'جلسة'} مع ${normalized.participant_name || 'المشارك'}`
            : '';
    });

    times.forEach(time => {
        time.textContent = normalized?.scheduled_at_label || '';
    });

    buttons.forEach(button => {
        button.href = normalized?.join_url || '#';
        button.classList.toggle('disabled', !normalized?.join_url);
        button.classList.toggle('d-none', !(normalized && normalized.can_join_now));
    });
}

function initializeTeacherRealtime() {
    const liveIndicator = document.querySelector('[data-live-request-indicator]');
    const dashboardIndicator = document.querySelector('[data-teacher-realtime]');
    const indicator = liveIndicator || dashboardIndicator;

    if (!indicator) {
        return;
    }

    const isLiveIndicator = Boolean(liveIndicator);
    const prompt = document.getElementById('teacherJoinSessionPrompt');
    const realtimeChannel = indicator.dataset.realtimeChannel;
    const countNode = liveIndicator?.querySelector('[data-live-request-count]') || null;
    const summaryNode = liveIndicator?.querySelector('[data-live-toast-summary]') || null;
    const metaNode = liveIndicator?.querySelector('[data-live-toast-meta]') || null;
    const acceptForm = liveIndicator?.querySelector('[data-live-accept-form]') || null;
    const rejectForm = liveIndicator?.querySelector('[data-live-reject-form]') || null;
    const requestCard = liveIndicator?.querySelector('[data-teacher-live-card]') || null;
    const initialPayload = safeJsonParse(indicator.dataset.activeSession, null);
    const pollUrl = indicator.dataset.pollUrl;

    renderQuickJoinTargets('teacher', initialPayload);
    applyJoinPromptPayload(prompt, initialPayload);

    const applyActiveSessionPayload = activeSessionPayload => {
        indicator.dataset.activeSession = JSON.stringify(activeSessionPayload);
        renderQuickJoinTargets('teacher', activeSessionPayload);
        applyJoinPromptPayload(prompt, activeSessionPayload);
    };

    const pollActiveState = () => {
        if (!pollUrl) {
            return;
        }

        fetch(pollUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then(response => response.json())
            .then(payload => {
                applyActiveSessionPayload(payload?.active_session_payload ?? null);

                if (!isLiveIndicator || !countNode || !summaryNode || !metaNode || !acceptForm || !rejectForm || !requestCard) {
                    return;
                }

                const requestsPayload = payload?.requests ? payload : { count: 0, requests: [] };
                countNode.textContent = String(requestsPayload.count || 0);
                countNode.classList.toggle('d-none', !requestsPayload.count);

                if (!requestsPayload.count) {
                    requestCard.classList.add('d-none');
                    return;
                }

                const latest = requestsPayload.requests?.[0];

                if (!latest) {
                    requestCard.classList.add('d-none');
                    return;
                }

                summaryNode.textContent = `${latest.student_name} طلب ${latest.subject_name}`;
                metaNode.textContent = latest.requested_at || '';
                acceptForm.action = latest.accept_url || '';
                rejectForm.action = latest.reject_url || '';
                requestCard.classList.remove('d-none');
            })
            .catch(() => { });
    };

    if (window.Echo) {
        window.Echo.channel(realtimeChannel).listen('.teacher.realtime.updated', payload => {
            const activeSessionPayload = payload?.active_session_payload
                ?? joinPayloadFromSessionUpdate('teacher', payload?.session_update)
                ?? null;

            applyActiveSessionPayload(activeSessionPayload);
            updateRealtimeBadgeCounts(payload);

            if (payload?.session_update) {
                updateSessionTriggerPayload('[data-session-trigger]', payload.session_update);
            }

            if (!isLiveIndicator) {
                return;
            }

            if (!countNode || !summaryNode || !metaNode || !acceptForm || !rejectForm || !requestCard) {
                return;
            }

            const requestsPayload = payload?.live_requests ?? { count: 0, requests: [] };
            countNode.textContent = String(requestsPayload.count || 0);
            countNode.classList.toggle('d-none', !requestsPayload.count);

            if (!requestsPayload.count) {
                requestCard.classList.add('d-none');
                return;
            }

            const latest = requestsPayload.requests?.[0];

            if (!latest) {
                requestCard.classList.add('d-none');
                return;
            }

            summaryNode.textContent = `${latest.student_name} طلب ${latest.subject_name}`;
            metaNode.textContent = latest.requested_at || '';
            acceptForm.action = latest.accept_url || '';
            rejectForm.action = latest.reject_url || '';
            requestCard.classList.remove('d-none');
        });
    }

    if (pollUrl) {
        pollActiveState();
        window.setInterval(pollActiveState, 5000);
    }
}

function initializeStudentRealtime() {
    const indicator = document.querySelector('[data-student-realtime]');

    if (!indicator) {
        return;
    }

    const prompt = document.getElementById('studentJoinSessionPrompt');
    const realtimeChannel = indicator.dataset.realtimeChannel;
    const initialPayload = safeJsonParse(indicator.dataset.activeSession, null);
    const pollUrl = indicator.dataset.pollUrl;

    renderQuickJoinTargets('student', initialPayload);
    applyJoinPromptPayload(prompt, initialPayload);

    const applyActiveSessionPayload = activeSessionPayload => {
        indicator.dataset.activeSession = JSON.stringify(activeSessionPayload);
        renderQuickJoinTargets('student', activeSessionPayload);
        applyJoinPromptPayload(prompt, activeSessionPayload);
    };

    const pollActiveState = () => {
        if (!pollUrl) {
            return;
        }

        fetch(pollUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then(response => response.json())
            .then(payload => {
                applyActiveSessionPayload(payload?.active_session_payload ?? null);
            })
            .catch(() => { });
    };

    if (window.Echo) {
        window.Echo.channel(realtimeChannel).listen('.student.realtime.updated', payload => {
            const activeSessionPayload = payload?.active_session_payload
                ?? joinPayloadFromSessionUpdate('student', payload?.session_update)
                ?? null;

            applyActiveSessionPayload(activeSessionPayload);
            updateRealtimeBadgeCounts(payload);

            if (payload?.live_request_update?.status === 'accepted' && activeSessionPayload) {
                sessionStorage.removeItem(joinPromptStorageKey(activeSessionPayload));
                applyJoinPromptPayload(prompt, activeSessionPayload, { forceShow: true });
                pollActiveState();
            }

            if (payload?.session_update) {
                updateSessionTriggerPayload('[data-student-session-trigger]', payload.session_update);
            }
        });
    }

    pollActiveState();
    window.setInterval(pollActiveState, 5000);
}

function extractErrorMessage(error, fallback) {
    const errors = error?.response?.data?.errors;

    if (errors) {
        const firstField = Object.keys(errors)[0];
        const firstMessage = firstField ? errors[firstField]?.[0] : null;

        if (firstMessage) {
            return firstMessage;
        }
    }

    return error?.response?.data?.message || fallback;
}

function updateRealtimeBadgeCounts(payload) {
    if (!payload?.unread_counts) {
        return;
    }

    Object.entries(payload.unread_counts).forEach(([badgeKey, count]) => {
        document.querySelectorAll(`[data-dashboard-badge="${badgeKey}"]`).forEach(node => {
            node.textContent = String(count || 0);
            node.classList.toggle('d-none', !count);
        });
    });
}

function initializeAdminRealtime() {
    const indicator = document.querySelector('[data-admin-realtime]');

    if (!indicator || !window.Echo) {
        return;
    }

    const realtimeChannel = indicator.dataset.realtimeChannel;

    window.Echo.channel(realtimeChannel).listen('.admin.realtime.updated', payload => {
        updateRealtimeBadgeCounts(payload);
    });
}

function initializeTeacherChart() {
    initializeMixedChart({
        rootSelector: '[data-teacher-chart]',
        totalPrimarySelector: '[data-chart-profit]',
        totalSecondarySelector: '[data-chart-sessions]',
        trendSelector: '[data-chart-trend]',
        primaryKey: 'profits',
        secondaryKey: 'sessions',
        primaryLabel: 'الأرباح',
        secondaryLabel: 'عدد الجلسات',
        primaryType: 'line',
        secondaryType: 'column',
        colors: ['#22c55e', '#7c3aed'],
        yaxis: [
            { title: { text: 'الجلسات' } },
            { opposite: true, title: { text: 'الأرباح' } },
        ],
    });
}

function initializeStudentChart() {
    initializeMixedChart({
        rootSelector: '[data-student-chart]',
        totalPrimarySelector: '[data-chart-hours]',
        totalSecondarySelector: '[data-chart-sessions]',
        trendSelector: '[data-chart-trend]',
        primaryKey: 'hours',
        secondaryKey: 'sessions',
        primaryLabel: 'الساعات',
        secondaryLabel: 'عدد الجلسات',
        primaryType: 'line',
        secondaryType: 'column',
        colors: ['#38bdf8', '#2563eb'],
        yaxis: [
            { title: { text: 'الجلسات' } },
            { opposite: true, title: { text: 'الساعات' } },
        ],
    });
}

function initializeMixedChart(config) {
    const chartRoot = document.querySelector(config.rootSelector);

    if (!chartRoot) {
        return;
    }

    const chartData = JSON.parse(chartRoot.dataset.chart ?? '{}');
    const filters = chartRoot.querySelectorAll('.teacher-chart-filter, .student-chart-filter');
    const primaryNode = chartRoot.querySelector(config.totalPrimarySelector);
    const secondaryNode = chartRoot.querySelector(config.totalSecondarySelector);
    const trendNode = chartRoot.querySelector(config.trendSelector);
    const canvas = chartRoot.querySelector('[data-chart-canvas]');
    let chart;

    const renderRange = range => {
        const current = chartData[range];

        if (!current || !canvas) {
            return;
        }

        filters.forEach(filter => {
            filter.classList.toggle('active', filter.dataset.range === range);
        });

        if (primaryNode) {
            primaryNode.textContent = current.totalProfit || current.totalHours || '';
        }

        if (secondaryNode) {
            secondaryNode.textContent = current.totalSessions || '';
        }

        if (trendNode) {
            trendNode.textContent = current.trend || '';
        }

        if (!window.ApexCharts) {
            return;
        }

        const options = {
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif',
            },
            series: [
                {
                    name: config.secondaryLabel,
                    type: config.secondaryType,
                    data: current[config.secondaryKey],
                },
                {
                    name: config.primaryLabel,
                    type: config.primaryType,
                    data: current[config.primaryKey],
                },
            ],
            stroke: {
                width: [0, 4],
                curve: 'smooth',
            },
            colors: config.colors,
            fill: {
                opacity: [0.9, 1],
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    opacityFrom: 0.92,
                    opacityTo: 0.35,
                    stops: [0, 100],
                },
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '42%',
                },
            },
            dataLabels: {
                enabled: false,
            },
            xaxis: {
                categories: current.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: config.yaxis,
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.18)',
                strokeDashArray: 4,
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
            },
            tooltip: {
                shared: true,
                intersect: false,
            },
        };

        if (chart) {
            chart.updateOptions(options, true, true);
        } else {
            chart = new window.ApexCharts(canvas, options);
            chart.render();
        }
    };

    filters.forEach(filter => {
        filter.addEventListener('click', () => renderRange(filter.dataset.range));
    });

    renderRange(chartRoot.dataset.chartDefault || 'month');
}

function initializeTeacherSessionPanel() {
    const triggers = document.querySelectorAll('[data-session-trigger]');
    const detailsRoot = document.querySelector('[data-session-details]');

    if (!triggers.length || !detailsRoot) {
        return;
    }

    const student = detailsRoot.querySelector('[data-session-student]');
    const subject = detailsRoot.querySelector('[data-session-subject]');
    const status = detailsRoot.querySelector('[data-session-status]');
    const scheduled = detailsRoot.querySelector('[data-session-scheduled]');
    const notes = detailsRoot.querySelector('[data-session-notes]');
    const recording = detailsRoot.querySelector('[data-session-recording]');
    const chat = detailsRoot.querySelector('[data-session-chat]');
    const cancellation = detailsRoot.querySelector('[data-session-cancellation]');
    const cancellationCard = detailsRoot.querySelector('[data-cancellation-card]');
    const cancelForm = detailsRoot.querySelector('[data-cancel-form]');
    const confirmCard = detailsRoot.querySelector('[data-confirm-card]');
    const confirmForm = detailsRoot.querySelector('[data-confirm-form]');
    const complaints = detailsRoot.querySelector('[data-session-complaints]');
    const joinCard = detailsRoot.querySelector('[data-session-join-card]');
    const joinLink = detailsRoot.querySelector('[data-session-join-link]');
    const studentSummary = detailsRoot.querySelector('[data-session-student-summary]');
    const recordingNote = detailsRoot.querySelector('[data-session-recording-note]');
    const files = detailsRoot.querySelector('[data-session-files]');

    const statusLabel = value => {
        if (value === 'completed') {
            return 'مكتملة';
        }

        if (value === 'cancelled') {
            return 'ملغية';
        }

        return 'قادمة';
    };

    const render = payload => {
        if (student) student.textContent = payload.student_name;
        if (subject) subject.textContent = payload.subject_name;
        if (status) status.textContent = statusLabel(payload.status);
        if (scheduled) scheduled.textContent = payload.scheduled_at ?? '-';
        if (notes) notes.textContent = payload.notes || 'لا توجد ملاحظات مضافة.';
        if (recording) recording.textContent = payload.recording_url ? 'متاح' : 'غير متوفر';
        if (chat) chat.textContent = payload.chat_excerpt || 'لا توجد محادثة محفوظة.';
        if (studentSummary) {
            studentSummary.textContent = payload.student_summary_notes || 'لا توجد ملاحظات مضافة بعد.';
        }
        if (recordingNote) {
            recordingNote.textContent = payload.recording_note || '';
        }

        if (cancellation && cancellationCard) {
            if (payload.cancellation_reason) {
                cancellation.textContent = payload.cancellation_reason;
                cancellationCard.style.display = '';
            } else {
                cancellationCard.style.display = 'none';
            }
        }

        if (cancelForm) {
            cancelForm.style.display = payload.can_cancel ? '' : 'none';
            cancelForm.action = payload.cancel_url || '';
        }

        if (confirmCard && confirmForm) {
            confirmCard.style.display = payload.can_confirm ? '' : 'none';
            confirmForm.action = payload.confirm_url || '';
        }

        if (joinCard && joinLink) {
            joinCard.style.display = payload.can_join_now ? '' : 'none';
            joinLink.href = payload.join_url || '#';
        }

        if (files) {
            renderSessionFiles(files, payload.files, 'لا توجد ملفات مرفوعة لهذه الجلسة.');
        }

        if (complaints) {
            complaints.innerHTML = '';

            if (!payload.complaints?.length) {
                complaints.innerHTML = '<div class="text-muted">لا توجد شكاوى مرتبطة بهذه الجلسة.</div>';
                return;
            }

            payload.complaints.forEach((complaint, index) => {
                const row = document.createElement('div');
                row.className = `d-flex justify-content-between align-items-center py-2 ${index === payload.complaints.length - 1 ? '' : 'border-bottom'}`;
                row.innerHTML = `
                    <div>
                        <div class="fw-semibold">${complaint.title}</div>
                        <div class="small text-muted">${complaint.submitted_at ?? ''}</div>
                    </div>
                    <span class="teacher-chip teacher-chip-warning">${complaint.status}</span>
                `;
                complaints.appendChild(row);
            });
        }
    };

    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            triggers.forEach(item => item.classList.remove('active'));
            trigger.classList.add('active');
            render(JSON.parse(trigger.dataset.session));
        });
    });

    const activeTrigger = Array.from(triggers).find(trigger => trigger.classList.contains('active')) || triggers[0];

    if (activeTrigger?.dataset.session) {
        render(JSON.parse(activeTrigger.dataset.session));
    }
}

function initializeStudentSessionPanel() {
    const triggers = document.querySelectorAll('[data-student-session-trigger]');
    const detailsRoot = document.querySelector('[data-student-session-details]');

    if (!triggers.length || !detailsRoot) {
        return;
    }

    const teacher = detailsRoot.querySelector('[data-session-teacher]');
    const subject = detailsRoot.querySelector('[data-session-subject]');
    const status = detailsRoot.querySelector('[data-session-status]');
    const scheduled = detailsRoot.querySelector('[data-session-scheduled]');
    const notes = detailsRoot.querySelector('[data-session-notes]');
    const recording = detailsRoot.querySelector('[data-session-recording]');
    const chat = detailsRoot.querySelector('[data-session-chat]');
    const cancellation = detailsRoot.querySelector('[data-session-cancellation]');
    const cancellationCard = detailsRoot.querySelector('[data-student-cancellation-card]');
    const studentConfirmed = detailsRoot.querySelector('[data-session-student-confirmed]');
    const teacherConfirmed = detailsRoot.querySelector('[data-session-teacher-confirmed]');
    const confirmForm = detailsRoot.querySelector('[data-student-confirm-form]');
    const cancelCard = detailsRoot.querySelector('[data-student-cancel-card]');
    const cancelForm = document.querySelector('#studentCancelSessionModal [data-student-cancel-form]');
    const complaints = detailsRoot.querySelector('[data-session-complaints]');
    const joinCard = detailsRoot.querySelector('[data-session-join-card]');
    const joinLink = detailsRoot.querySelector('[data-session-join-link]');
    const studentSummary = detailsRoot.querySelector('[data-session-student-summary]');
    const recordingNote = detailsRoot.querySelector('[data-session-recording-note]');
    const files = detailsRoot.querySelector('[data-session-files]');

    const statusLabel = value => {
        if (value === 'completed') {
            return 'مكتملة';
        }

        if (value === 'cancelled') {
            return 'ملغية';
        }

        return 'قادمة';
    };

    const render = payload => {
        detailsRoot.style.display = '';
        if (teacher) teacher.textContent = payload.teacher_name;
        if (subject) subject.textContent = payload.subject_name;
        if (status) status.textContent = statusLabel(payload.status);
        if (scheduled) scheduled.textContent = payload.scheduled_at ?? '-';
        if (notes) notes.textContent = payload.notes || 'لا توجد ملاحظات مضافة.';
        if (recording) recording.textContent = payload.recording_url ? 'متاح' : 'غير متوفر';
        if (chat) chat.textContent = payload.chat_excerpt || 'لا توجد محادثة محفوظة.';
        if (studentConfirmed) studentConfirmed.textContent = payload.student_confirmed ? 'تم التأكيد' : 'بانتظار التأكيد';
        if (teacherConfirmed) teacherConfirmed.textContent = payload.teacher_confirmed ? 'تم التأكيد' : 'بانتظار التأكيد';
        if (studentSummary) {
            studentSummary.textContent = payload.student_summary_notes || 'ستظهر هذه الملاحظات بعد انتهاء الجلسة.';
        }
        if (recordingNote) {
            recordingNote.textContent = payload.recording_note || '';
        }

        if (cancellation && cancellationCard) {
            if (payload.cancellation_reason) {
                cancellation.textContent = payload.cancellation_reason;
                cancellationCard.style.display = '';
            } else {
                cancellationCard.style.display = 'none';
            }
        }

        if (confirmForm) {
            confirmForm.style.display = payload.can_confirm ? '' : 'none';
            confirmForm.action = payload.confirm_url || '';
        }

        if (cancelCard && cancelForm) {
            cancelCard.style.display = payload.can_cancel ? '' : 'none';
            cancelForm.action = payload.cancel_url || '';
        }

        if (joinCard && joinLink) {
            joinCard.style.display = payload.can_join_now ? '' : 'none';
            joinLink.href = payload.join_url || '#';
        }

        if (files) {
            renderSessionFiles(files, payload.files, 'لا توجد ملفات مرفوعة لهذه الجلسة.');
        }

        if (complaints) {
            complaints.innerHTML = '';

            if (!payload.complaints?.length) {
                complaints.innerHTML = '<div class="text-muted">لا توجد شكاوى مرتبطة بهذه الجلسة.</div>';
                return;
            }

            payload.complaints.forEach((complaint, index) => {
                const row = document.createElement('div');
                row.className = `d-flex justify-content-between align-items-center py-2 ${index === payload.complaints.length - 1 ? '' : 'border-bottom'}`;
                row.innerHTML = `
                    <div>
                        <div class="fw-semibold">${complaint.title}</div>
                        <div class="small text-muted">${complaint.submitted_at ?? ''}</div>
                    </div>
                    <span class="student-chip student-chip-warning">${complaint.status}</span>
                `;
                complaints.appendChild(row);
            });
        }
    };

    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            triggers.forEach(item => item.classList.remove('active'));
            trigger.classList.add('active');
            render(JSON.parse(trigger.dataset.session));
        });
    });

    const activeTrigger = Array.from(triggers).find(trigger => trigger.classList.contains('active')) || triggers[0];

    if (activeTrigger?.dataset.session) {
        render(JSON.parse(activeTrigger.dataset.session));
    }
}

function initializeTeacherLivePolling() {
    const indicator = document.querySelector('[data-live-request-indicator]');
    const card = indicator?.querySelector('[data-teacher-live-card]');

    if (!indicator) {
        return;
    }

    const countNode = indicator.querySelector('[data-live-request-count]');
    const summaryNode = indicator.querySelector('[data-live-toast-summary]');
    const metaNode = indicator.querySelector('[data-live-toast-meta]');
    const acceptForm = indicator.querySelector('[data-live-accept-form]');
    const rejectForm = indicator.querySelector('[data-live-reject-form]');
    const pollUrl = indicator.dataset.pollUrl;

    const render = payload => {
        if (countNode) {
            countNode.textContent = String(payload.count || 0);
            countNode.classList.toggle('d-none', !payload.count);
        }

        if (!card || !summaryNode || !metaNode || !acceptForm || !rejectForm) {
            return;
        }

        if (payload.count > 0) {
            const latest = payload.requests[0];
            summaryNode.textContent = `${latest.student_name} طلب ${latest.subject_name}`;
            metaNode.textContent = latest.requested_at || '';
            acceptForm.action = latest.accept_url || '';
            rejectForm.action = latest.reject_url || '';
            card.classList.remove('d-none');
        } else {
            card.classList.add('d-none');
        }
    };

    const poll = () => {
        fetch(pollUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then(response => response.json())
            .then(render)
            .catch(() => { });
    };

    poll();
    window.setInterval(poll, 20000);
}

function initializeBookingModeForms() {
    const forms = document.querySelectorAll('[data-booking-mode-root]');

    forms.forEach(form => {
        const inputs = form.querySelectorAll('[data-booking-mode-input]');
        const scheduledFields = form.querySelector('[data-scheduled-booking-fields]');

        const render = () => {
            const selected = Array.from(inputs).find(input => input.checked)?.value || 'instant';

            if (scheduledFields) {
                scheduledFields.style.display = selected === 'scheduled' ? '' : 'none';
            }
        };

        inputs.forEach(input => {
            input.addEventListener('change', render);
        });
        form.addEventListener('submit', async event => {
            if (form.querySelector('[data-booking-balance-check]')) {
                return;
            }

            if (form.dataset.bookingCostConfirmHandled === '1') {
                return;
            }

            event.preventDefault();

            if (!await ensureMediaInputsForAction('الحجز')) {
                return;
            }

            requestBookingConfirmation(form);
        });

        render();
    });

    const bookingModal = document.getElementById('studentBookingModal');

    if (bookingModal) {
        bookingModal.addEventListener('shown.bs.modal', () => {
            forms.forEach(form => {
                delete form.dataset.bookingCostConfirmHandled;
            });
        });
    }
}

function initializeTeacherLiveAvailabilitySwitches() {
    const switches = document.querySelectorAll('[data-live-availability-switch]');

    switches.forEach(input => {
        input.dataset.previousChecked = input.checked ? '1' : '0';

        input.addEventListener('change', async () => {
            const form = input.closest('form');
            const previousChecked = input.dataset.previousChecked === '1';

            if (!form) {
                return;
            }

            if (input.checked && !await ensureMediaInputsForAction('تفعيل الجلسات المباشرة')) {
                input.checked = previousChecked;
                return;
            }

            form.dataset.deviceCheckPassed = '1';
            input.dataset.previousChecked = input.checked ? '1' : '0';
            form.requestSubmit ? form.requestSubmit() : form.submit();
        });
    });
}

function initializeTeacherLivePresence() {
    const indicator = document.querySelector('[data-live-request-indicator]');

    if (!indicator?.dataset.presenceUrl) {
        return;
    }

    let intentionalNavigation = false;
    const token = indicator.querySelector('input[name="_token"]')?.value;

    const postPresence = url => {
        if (!url || !token) {
            return;
        }

        const data = new FormData();
        data.append('_token', token);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, data);
            return;
        }

        fetch(url, {
            method: 'POST',
            body: data,
            keepalive: true,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => { });
    };

    document.addEventListener('submit', () => {
        intentionalNavigation = true;
    }, true);

    document.addEventListener('click', event => {
        const link = event.target?.closest?.('a[href]');

        if (!link || link.target === '_blank') {
            return;
        }

        try {
            const href = new URL(link.href, window.location.href);

            if (href.origin === window.location.origin) {
                intentionalNavigation = true;
            }
        } catch (error) {
            intentionalNavigation = true;
        }
    }, true);

    postPresence(indicator.dataset.presenceUrl);
    window.setInterval(() => postPresence(indicator.dataset.presenceUrl), 15000);

    window.addEventListener('pagehide', () => {
        if (!intentionalNavigation) {
            postPresence(indicator.dataset.offlineUrl);
        }
    });
}

function initializeDeviceCheckForms() {
    const forms = document.querySelectorAll('[data-device-check-form]');

    forms.forEach(form => {
        form.addEventListener('submit', async event => {
            if (form.dataset.deviceCheckPassed === '1') {
                delete form.dataset.deviceCheckPassed;
                return;
            }

            // Immediately block default submission and other listeners
            event.preventDefault();
            event.stopImmediatePropagation();

            const ok = await ensureMediaInputsForAction('متابعة هذا الإجراء');

            if (!ok) {
                // device check failed; do not proceed
                return;
            }

            // device check passed — submit programmatically so other handlers run normally
            form.dataset.deviceCheckPassed = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }, true);
    });
}

function initializeJoinSessionLinks() {
    const joinLinks = document.querySelectorAll('a[data-join-session-link]');

    joinLinks.forEach(link => {
        link.addEventListener('click', async event => {
            if (!await ensureMediaInputsForAction('الانضمام للجلسة')) {
                event.preventDefault();
            }
        });
    });
}

function initializeTeacherSlotPicker() {
    const pickers = document.querySelectorAll('[data-teacher-slot-config]');

    pickers.forEach(picker => {
        const slots = JSON.parse(picker.dataset.slots || '[]');
        const daySelect = picker.querySelector('[data-slot-day]');
        const startSelect = picker.querySelector('[data-slot-start]');
        const durationSelect = picker.querySelector('[data-slot-duration]');
        const warning = picker.querySelector('[data-slot-warning]');
        const form = picker.closest('form');
        const slotHidden = form?.querySelector('[data-slot-hidden]');
        const availabilityHidden = form?.querySelector('[data-availability-hidden]');
        const submitButton = form?.querySelector('button[type="submit"]');

        if (!daySelect || !startSelect || !durationSelect || !slotHidden || !availabilityHidden) {
            return;
        }

        const grouped = new Map();

        slots.forEach(slot => {
            if (!grouped.has(String(slot.day_of_week))) {
                grouped.set(String(slot.day_of_week), []);
            }

            grouped.get(String(slot.day_of_week)).push(slot);
        });

        const uniqueDays = Array.from(grouped.keys());
        daySelect.innerHTML = uniqueDays.map(day => {
            const first = grouped.get(day)?.[0];
            const label = first?.label?.split(' / ')[0] || day;

            return `<option value="${day}">${label}</option>`;
        }).join('');

        const renderStarts = () => {
            const selectedDay = daySelect.value;
            const daySlots = grouped.get(selectedDay) || [];

            startSelect.innerHTML = daySlots.map(slot => {
                const timeLabel = slot.label?.split(' / ')[1] || slot.starts_at;

                return `<option value="${slot.starts_at}" data-availability-id="${slot.availability_id}" data-max-duration="${slot.max_duration}">${timeLabel}</option>`;
            }).join('');
        };

        const syncSelection = () => {
            const option = startSelect.options[startSelect.selectedIndex];

            if (!option) {
                slotHidden.value = '';
                availabilityHidden.value = '';
                durationSelect.classList.remove('is-invalid');
                if (submitButton) {
                    submitButton.dataset.slotDisabled = '1';
                    submitButton.disabled = true;
                }
                if (warning) {
                    warning.classList.add('d-none');
                    warning.textContent = '';
                }
                return;
            }

            const maxDuration = Number(option.dataset.maxDuration || '1');
            const requestedDuration = Number(durationSelect.value || '1');
            slotHidden.value = option.value;
            availabilityHidden.value = option.dataset.availabilityId || '';

            if (requestedDuration > maxDuration) {
                durationSelect.classList.add('is-invalid');
                if (submitButton) {
                    submitButton.dataset.slotDisabled = '1';
                    submitButton.disabled = true;
                }
                if (warning) {
                    warning.classList.remove('d-none');
                    warning.textContent = `الحد الأقصى المتاح من هذه الساعة هو ${maxDuration} ساعة فقط.`;
                }
            } else {
                durationSelect.classList.remove('is-invalid');
                if (submitButton) {
                    submitButton.dataset.slotDisabled = '0';
                    submitButton.disabled = false;
                }
                if (warning) {
                    warning.classList.add('d-none');
                    warning.textContent = '';
                }
            }
        };

        daySelect.addEventListener('change', () => {
            renderStarts();
            syncSelection();
        });
        startSelect.addEventListener('change', syncSelection);
        durationSelect.addEventListener('change', syncSelection);

        renderStarts();
        syncSelection();
    });
}

function initializeBookingBalanceChecks() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        const config = form.querySelector('[data-booking-balance-check]');

        if (!config) {
            return;
        }

        const subjectInput = form.querySelector('[data-booking-subject]');
        const durationInput = form.querySelector('[data-booking-duration]') || form.querySelector('select[name="duration_hours"]');
        const modeInputs = form.querySelectorAll('[data-booking-mode-input]');
        const warning = form.querySelector('[data-booking-balance-warning]');
        const submitButton = form.querySelector('[data-booking-submit]') || form.querySelector('button[type="submit"]');
        const hourlyRateNode = form.querySelector('[data-booking-hourly-rate]');
        const totalNode = form.querySelector('[data-booking-total]');
        const balance = Number(config.dataset.studentBalance || '0');
        const subjectRates = safeJsonParse(config.dataset.subjectRates || '{}', {});
        const hardDisabled = Boolean(submitButton?.disabled && submitButton?.dataset.slotDisabled !== '0');

        const render = () => {
            const selectedMode = Array.from(modeInputs).find(input => input.checked)?.value || 'scheduled';
            const subjectName = subjectInput?.value || '';
            const hourlyRate = Number(subjectRates?.[subjectName] || 0);
            const durationHours = selectedMode === 'instant' ? 1 : Number(durationInput?.value || 1);
            const total = hourlyRate * durationHours;
            const insufficient = total > 0 && balance < total;

            if (hourlyRateNode) {
                hourlyRateNode.textContent = String(Math.round(hourlyRate));
            }

            if (totalNode) {
                totalNode.textContent = String(Math.round(total));
            }

            if (warning) {
                warning.classList.toggle('d-none', !insufficient);
                warning.textContent = insufficient
                    ? `رصيدك الحالي لا يكفي لهذه الجلسة. المطلوب ${Math.round(total)} والمتوفر ${Math.round(balance)}.`
                    : '';
            }

            if (submitButton) {
                const slotDisabled = submitButton.dataset.slotDisabled === '1';
                submitButton.disabled = hardDisabled || slotDisabled || insufficient;
            }
        };

        form.addEventListener('submit', event => {
            const subjectName = subjectInput?.value || '';
            const hourlyRate = Number(subjectRates?.[subjectName] || 0);
            const selectedMode = Array.from(modeInputs).find(input => input.checked)?.value || 'scheduled';
            const durationHours = selectedMode === 'instant' ? 1 : Number(durationInput?.value || 1);
            const total = hourlyRate * durationHours;

            if (total > 0 && balance < total) {
                event.preventDefault();
                showAppToast('رصيدك الحالي لا يكفي لحجز هذه الجلسة.', 'danger');
                return;
            }
            if (form.dataset.bookingCostConfirmHandled === '1') {
                return;
            }

            event.preventDefault();
            requestBookingConfirmation(form, {
                mode: selectedMode,
                teacher_name: '',
                hourly_rate: hourlyRate,
                total,
                can_afford: true,
            });
        });

        subjectInput?.addEventListener('change', render);
        durationInput?.addEventListener('change', render);
        modeInputs.forEach(input => input.addEventListener('change', render));
        render();
    });
}

function initializeWalletForms() {
    const forms = document.querySelectorAll('[data-wallet-form]');

    forms.forEach(form => {
        const amountInput = form.querySelector('[data-wallet-amount]');
        const proofInput = form.querySelector('[data-wallet-proof]');
        const submitButton = form.querySelector('[data-wallet-submit]');
        const fillMaxButton = form.querySelector('[data-wallet-fill-max]');
        const amountError = form.querySelector('[data-wallet-amount-error]');
        const proofError = form.querySelector('[data-wallet-proof-error]');
        const maxAmount = Number(form.dataset.walletMax || '0');

        const render = () => {
            const amount = Number(amountInput?.value || '0');
            const file = proofInput?.files?.[0] || null;
            const amountValid = Number.isFinite(amount) && amount >= 50 && (!maxAmount || amount <= maxAmount);
            const proofValid = !proofInput || (file && file.type.startsWith('image/'));

            if (amountError) {
                const amountMessage = amount > 0 && amount < 50
                    ? 'أقل مبلغ مسموح به هو 50.'
                    : (maxAmount && amount > maxAmount ? 'المبلغ المطلوب أكبر من الرصيد الحالي.' : '');
                amountError.textContent = amountMessage || 'أقل مبلغ مسموح به هو 50.';
                amountError.classList.toggle('d-none', !amountMessage);
            }

            if (proofError) {
                proofError.classList.toggle('d-none', proofValid || !file);
            }

            if (submitButton) {
                submitButton.disabled = !(amountValid && proofValid);
            }
        };

        fillMaxButton?.addEventListener('click', () => {
            if (!amountInput) {
                return;
            }

            amountInput.value = maxAmount > 0 ? String(Math.floor(maxAmount)) : '';
            render();
        });

        amountInput?.addEventListener('input', render);
        proofInput?.addEventListener('change', render);
        render();
    });
}

function renderSessionFiles(container, files, emptyText) {
    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (!files?.length) {
        container.innerHTML = `<div class="text-muted">${emptyText}</div>`;
        return;
    }

    files.forEach((file, index) => {
        const row = document.createElement('div');
        row.className = `py-2 ${index === files.length - 1 ? '' : 'border-bottom'}`;
        row.innerHTML = `<a href="${file.url || file.file_url}" target="_blank" class="text-decoration-none">${file.name || file.original_name}</a>`;
        container.appendChild(row);
    });
}

function initializeJoinSessionPrompts() {
    const prompts = document.querySelectorAll('[data-join-session-modal]');

    prompts.forEach(prompt => {
        applyJoinPromptPayload(prompt, safeJsonParse(prompt.dataset.joinSessionModal, null));
    });
}

function initializeBookingResult() {
    const node = document.querySelector('[data-booking-result]');

    if (!node) {
        return;
    }

    const payload = safeJsonParse(node.dataset.bookingPayload, null);
    const prompt = document.getElementById('studentJoinSessionPrompt');

    if (!prompt) {
        return;
    }

    applyJoinPromptPayload(prompt, payload, { forceShow: true });
}

async function initializeVideoCallPage() {
    const root = document.querySelector('[data-video-call-app]');

    if (!root) {
        return;
    }

    const [{ createApp }, { default: VideoCall }] = await Promise.all([
        import('vue'),
        import('./pages/VideoCall.vue'),
    ]);

    createApp(VideoCall, {
        config: safeJsonParse(root.dataset.videoCallConfig, {}),
    }).mount(root);
}

function initializeLiveSessionRoom() {
    const root = document.querySelector('[data-live-room]');

    if (!root) {
        return;
    }

    const config = JSON.parse(root.dataset.liveRoom || '{}');
    let roomState = config.initialState || {};
    let lastSignalId = 0;
    let joined = false;
    let joining = false;
    let ending = false;
    let redirecting = false;
    let offerSent = false;
    let peerConnection = null;
    let localStream = null;
    const remoteStream = new MediaStream();
    let mediaRecorder = null;
    let recordingChunkIndex = 0;
    let recordingUploadQueue = Promise.resolve();
    let recordingUploadStarted = false;
    let notesTimer = null;

    const remoteVideo = root.querySelector('[data-remote-video]');
    const localVideo = root.querySelector('[data-local-video]');
    const overlay = root.querySelector('[data-room-video-overlay]');
    const participantNode = root.querySelector('[data-room-participant]');
    const statusBadge = root.querySelector('[data-room-status-badge]');
    const remainingNode = root.querySelector('[data-room-remaining]');
    const joinButtons = root.querySelectorAll('[data-join-now]');
    const cameraButton = root.querySelector('[data-toggle-camera]');
    const micButton = root.querySelector('[data-toggle-mic]');
    const endingSoon = root.querySelector('[data-room-ending-soon]');
    const chatList = root.querySelector('[data-room-chat-list]');
    const chatForm = root.querySelector('[data-room-chat-form]');
    const fileList = root.querySelector('[data-room-file-list]');
    const fileForm = root.querySelector('[data-room-file-form]');
    const complaintList = root.querySelector('[data-room-complaint-list]');
    const complaintForm = root.querySelector('[data-room-complaint-form]');
    const teacherNotes = root.querySelector('[data-room-teacher-notes]');
    const studentNotes = root.querySelector('[data-room-student-notes]');
    const notesStatus = root.querySelector('[data-room-notes-status]');
    const studentSummary = root.querySelector('[data-room-student-summary]');
    const endButton = root.querySelector('[data-end-session]');
    const endConfirmModalElement = document.getElementById('liveSessionEndConfirmModal');
    const endConfirmMessage = document.querySelector('[data-room-end-confirm-message]');
    const endConfirmSubmit = document.querySelector('[data-room-end-confirm-submit]');
    const endModal = endConfirmModalElement ? bootstrap.Modal.getOrCreateInstance(endConfirmModalElement) : null;

    if (remoteVideo) {
        remoteVideo.srcObject = remoteStream;
    }

    const statusLabel = value => {
        if (value === 'in_progress') {
            return 'جارية الآن';
        }

        if (value === 'completed') {
            return 'انتهت';
        }

        if (value === 'cancelled') {
            return 'ملغية';
        }

        return 'قادمة';
    };

    const recordingMimeType = () => {
        const candidates = ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];

        return candidates.find(type => window.MediaRecorder && MediaRecorder.isTypeSupported(type)) || '';
    };

    const updateOverlay = message => {
        if (!overlay) {
            return;
        }

        overlay.classList.toggle('d-none', !message);
        const messageNode = overlay.querySelector('.live-room-video-message');

        if (messageNode) {
            messageNode.textContent = message || '';
        }
    };

    const renderMessages = messages => {
        if (!chatList) {
            return;
        }

        chatList.innerHTML = '';

        if (!messages?.length) {
            chatList.innerHTML = '<div class="text-muted">لا توجد رسائل بعد.</div>';
            return;
        }

        messages.forEach(message => {
            const row = document.createElement('div');
            row.className = `live-room-chat-bubble ${message.sender_role === config.role ? 'is-self' : ''}`;
            row.innerHTML = `
                <div class="live-room-chat-author">${message.sender_name || (message.sender_role === 'teacher' ? 'الأستاذ' : 'الطالب')}</div>
                <div>${message.message}</div>
            `;
            chatList.appendChild(row);
        });

        chatList.scrollTop = chatList.scrollHeight;
    };

    const renderComplaints = complaints => {
        if (!complaintList) {
            return;
        }

        complaintList.innerHTML = '';

        if (!complaints?.length) {
            complaintList.innerHTML = '<div class="text-muted">لا توجد شكاوى مسجلة بعد.</div>';
            return;
        }

        complaints.forEach(complaint => {
            const row = document.createElement('div');
            row.className = 'live-room-complaint-item';
            row.innerHTML = `
                <div class="fw-semibold">${complaint.title}</div>
                <div class="small text-muted">${complaint.status}</div>
            `;
            complaintList.appendChild(row);
        });
    };

    const updateJoinButtons = canJoin => {
        joinButtons.forEach(button => {
            button.disabled = !canJoin || joining;
            button.classList.toggle('d-none', !canJoin && joined);
        });
    };

    const renderState = async payload => {
        roomState = payload;
        payload.signals?.forEach(signal => {
            lastSignalId = Math.max(lastSignalId, Number(signal.id || 0));
        });

        if (participantNode) {
            participantNode.textContent = payload.session.participant_name || '';
        }

        if (statusBadge) {
            statusBadge.textContent = statusLabel(payload.session.status);
            statusBadge.dataset.status = payload.session.status || '';
        }

        if (teacherNotes && document.activeElement !== teacherNotes) {
            teacherNotes.value = payload.session.teacher_private_notes || '';
        }

        if (studentNotes && document.activeElement !== studentNotes) {
            studentNotes.value = payload.session.student_summary_notes || '';
        }

        if (studentSummary) {
            studentSummary.textContent = payload.session.student_summary_notes || 'ستظهر لك هذه الملاحظات بعد انتهاء الجلسة.';
        }

        renderMessages(payload.messages || []);
        renderSessionFiles(fileList, payload.files || [], 'لا توجد ملفات مرفوعة بعد.');
        renderComplaints(payload.complaints || []);
        updateJoinButtons(Boolean(payload.session.can_join_now));

        if (payload.session.status === 'completed') {
            if (payload.session.recording_url) {
                updateOverlay('انتهت الجلسة. التسجيل متوفر الآن.');
            } else {
                updateOverlay('انتهت الجلسة. ستتوفر الجلسة قريبًا.');
                startBackgroundRecordingUpload().catch(() => {
                    // Upload will keep retrying in subsequent poll cycles if needed.
                });
            }

            return;
        }

        if (payload.session.status === 'cancelled') {
            updateOverlay('تم إلغاء الجلسة.');
            return;
        }

        if (payload.session.can_join_now && !joined) {
            updateOverlay('يمكنك الانضمام الآن. سيتم تفعيل البث فور دخولك إلى الجلسة.');
        } else if (joined && remoteStream.getTracks().length === 0) {
            updateOverlay('تم دخولك إلى الغرفة، بانتظار اتصال الطرف الآخر.');
        } else {
            updateOverlay('');
        }

        if (payload.session.teacher_joined_at && payload.session.student_joined_at) {
            updateOverlay('');
        }

        for (const signal of payload.signals || []) {
            await handleSignal(signal);
        }

        renderTimer();
    };

    const renderTimer = () => {
        if (!remainingNode) {
            return;
        }

        const plannedEndAt = roomState?.session?.planned_end_at;

        if (!plannedEndAt) {
            remainingNode.textContent = '--:--:--';
            return;
        }

        const diff = new Date(plannedEndAt).getTime() - Date.now();

        if (diff <= 0) {
            remainingNode.textContent = '00:00:00';
            if (endingSoon) {
                endingSoon.classList.remove('d-none');
            }
            return;
        }

        const totalSeconds = Math.floor(diff / 1000);
        const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const seconds = String(totalSeconds % 60).padStart(2, '0');

        remainingNode.textContent = `${hours}:${minutes}:${seconds}`;

        if (endingSoon) {
            endingSoon.classList.toggle('d-none', diff > 600000);
        }
    };

    const ensurePeerConnection = async () => {
        if (peerConnection) {
            return peerConnection;
        }

        peerConnection = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
        });

        if (localStream) {
            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });
        }

        peerConnection.onicecandidate = event => {
            if (event.candidate) {
                sendSignal('candidate', event.candidate.toJSON());
            }
        };

        peerConnection.ontrack = event => {
            event.streams[0].getTracks().forEach(track => {
                if (!remoteStream.getTracks().some(existing => existing.id === track.id)) {
                    remoteStream.addTrack(track);
                }
            });

            updateOverlay('');
            startRecordingIfPossible();
        };

        peerConnection.onconnectionstatechange = () => {
            if (peerConnection.connectionState === 'failed') {
                updateOverlay('تعذر تثبيت الاتصال المباشر. حاول إعادة الانضمام للجلسة.');
            }
        };

        return peerConnection;
    };

    const ensureLocalMedia = async () => {
        if (localStream) {
            return localStream;
        }

        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            if (localVideo) {
                localVideo.srcObject = localStream;
            }
        } catch (error) {
            updateOverlay('تعذر الوصول إلى الكاميرا أو الميكروفون. امنح المتصفح صلاحية الوصول ثم أعد المحاولة.');
            throw error;
        }

        return localStream;
    };

    const sendSignal = async (signalType, payload) => {
        await axios.post(config.signalUrl, {
            signal_type: signalType,
            payload,
        });
    };

    const createOfferIfNeeded = async () => {
        if (config.role !== 'teacher' || offerSent || !joined) {
            return;
        }

        const pc = await ensurePeerConnection();

        if (pc.signalingState !== 'stable') {
            return;
        }

        const offer = await pc.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: true,
        });

        await pc.setLocalDescription(offer);
        await sendSignal('offer', offer.toJSON ? offer.toJSON() : { type: offer.type, sdp: offer.sdp });
        offerSent = true;
    };

    const handleSignal = async signal => {
        const pc = await ensurePeerConnection();

        if (signal.signal_type === 'offer') {
            if (!localStream) {
                await ensureLocalMedia();
            }

            await pc.setRemoteDescription(new RTCSessionDescription(signal.payload));
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            await sendSignal('answer', answer.toJSON ? answer.toJSON() : { type: answer.type, sdp: answer.sdp });
            return;
        }

        if (signal.signal_type === 'answer') {
            if (pc.signalingState !== 'closed') {
                await pc.setRemoteDescription(new RTCSessionDescription(signal.payload));
            }
            return;
        }

        if (signal.signal_type === 'candidate') {
            try {
                await pc.addIceCandidate(new RTCIceCandidate(signal.payload));
            } catch (error) {
                // Ignore late candidates that race with connection teardown.
            }
        }
    };

    const requestWithRetry = async (requestFn, attempts = 3, delayMs = 1000) => {
        let lastError;

        for (let attempt = 1; attempt <= attempts; attempt += 1) {
            try {
                return await requestFn();
            } catch (error) {
                lastError = error;

                if (attempt >= attempts) {
                    break;
                }

                await new Promise(resolve => setTimeout(resolve, delayMs * attempt));
            }
        }

        throw lastError;
    };

    const startRecordingIfPossible = () => {
        // Recording disabled by policy: skip all MediaRecorder work.
        return;
    };

    const stopRecordingAndUpload = async () => {
        if (window.DISABLE_SESSION_RECORDING) {
            return;
        }

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            await new Promise(resolve => {
                mediaRecorder.onstop = resolve;
                mediaRecorder.stop();
            });
        }

        await recordingUploadQueue;

        try {
            await requestWithRetry(
                () => axios.post(config.recordingUrl, { recording_finalize: true }),
                3,
                2000
            );
        } catch (error) {
            console.error('Recording finalization failed after retries:', error);
        }
    };

    const startBackgroundRecordingUpload = async () => {
        if (recordingUploadStarted) {
            return;
        }

        recordingUploadStarted = true;

        try {
            await stopRecordingAndUpload();
        } catch (error) {
            // Background upload failed, but we do not disrupt the room.
        }
    };

    const joinRoom = async () => {
        if (joining || joined) {
            return;
        }

        joining = true;

        try {
            await ensureLocalMedia();
            await axios.post(config.joinUrl);
            joined = true;
            await ensurePeerConnection();
            await createOfferIfNeeded();
            updateJoinButtons(false);
        } finally {
            joining = false;
        }
    };

    const pollState = async () => {
        const response = await axios.get(config.stateUrl, {
            params: { last_signal_id: lastSignalId },
        });

        await renderState(response.data);
    };

    const submitEndRequest = async confirmEnd => {
        if (ending) {
            return;
        }

        ending = true;

        try {
            const response = await axios.post(config.endUrl, { confirm_end: confirmEnd });

            startBackgroundRecordingUpload().catch(() => {
                // Keep the room responsive even if background upload fails.
            });

            // Do not redirect immediately; keep the room open until recording becomes available.
            return response;
        } catch (error) {
            const confirmMessage = error?.response?.data?.errors?.confirm_end?.[0];

            if (confirmMessage && endConfirmMessage) {
                endConfirmMessage.textContent = confirmMessage;
                endModal?.show();
                return;
            }

            throw error;
        } finally {
            ending = false;
        }
    };

    const endSession = async () => {
        const startedAt = roomState?.session?.started_at ? new Date(roomState.session.started_at) : null;
        const elapsedMinutes = startedAt ? Math.floor((Date.now() - startedAt.getTime()) / 60000) : 0;

        if (elapsedMinutes < 15) {
            await submitEndRequest(false);
            return;
        }

        if (endConfirmMessage) {
            endConfirmMessage.textContent = elapsedMinutes >= 15
                ? (config.role === 'student'
                    ? 'إنهاء الجلسة الآن يعني احتساب ثمنها كاملًا عليك. هل تريد المتابعة؟'
                    : 'هل أنت متأكد من إنهاء الجلسة الآن؟')
                : 'هل أنت متأكد من إنهاء الجلسة الآن؟';
        }

        endModal?.show();
    };

    joinButtons.forEach(button => {
        button.addEventListener('click', () => {
            joinRoom().catch(() => {
                updateOverlay('تعذر الانضمام إلى الجلسة الآن. حاول مجددًا بعد لحظات.');
            });
        });
    });

    if (cameraButton) {
        cameraButton.addEventListener('click', () => {
            localStream?.getVideoTracks().forEach(track => {
                track.enabled = !track.enabled;
                cameraButton.classList.toggle('is-off', !track.enabled);
            });
        });
    }

    if (micButton) {
        micButton.addEventListener('click', () => {
            localStream?.getAudioTracks().forEach(track => {
                track.enabled = !track.enabled;
                micButton.classList.toggle('is-off', !track.enabled);
            });
        });
    }

    if (chatForm) {
        chatForm.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(chatForm);
            const message = String(formData.get('message') || '').trim();

            if (!message) {
                return;
            }

            await axios.post(config.messageUrl, { message });
            chatForm.reset();
            await pollState();
        });
    }

    if (fileForm) {
        fileForm.addEventListener('submit', async event => {
            event.preventDefault();
            const formData = new FormData(fileForm);

            if (!formData.get('file')) {
                return;
            }

            await axios.post(config.fileUrl, formData);
            fileForm.reset();
            await pollState();
        });
    }

    if (complaintForm) {
        complaintForm.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(complaintForm);
            formData.set('title', String(formData.get('title') || '').trim());
            formData.set('description', String(formData.get('description') || '').trim());
            await axios.post(config.complaintUrl, formData);
            complaintForm.reset();
            await pollState();
        });
    }

    const scheduleNotesSave = () => {
        if (!notesStatus || !config.notesUrl) {
            return;
        }

        notesStatus.textContent = 'جاري تجهيز الحفظ...';
        window.clearTimeout(notesTimer);
        notesTimer = window.setTimeout(async () => {
            await axios.post(config.notesUrl, {
                teacher_private_notes: teacherNotes?.value || '',
                student_summary_notes: studentNotes?.value || '',
            });

            notesStatus.textContent = 'تم الحفظ.';
        }, 700);
    };

    teacherNotes?.addEventListener('input', scheduleNotesSave);
    studentNotes?.addEventListener('input', scheduleNotesSave);
    endButton?.addEventListener('click', endSession);

    endConfirmSubmit?.addEventListener('click', async () => {
        endModal?.hide();
        await submitEndRequest(true);
    });

    window.setInterval(renderTimer, 1000);
    window.setInterval(() => {
        pollState().catch(() => { });
    }, 2500);

    renderState(roomState).catch(() => { });

    if (roomState?.session?.can_join_now) {
        joinRoom().catch(() => { });
    } else {
        updateOverlay('سيتم تفعيل الانضمام بمجرد حلول موعد الجلسة وتأكيد الطرفين للحضور.');
    }

    window.addEventListener('beforeunload', () => {
        localStream?.getTracks().forEach(track => track.stop());
        peerConnection?.close();
    });
}
