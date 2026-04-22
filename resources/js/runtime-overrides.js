import * as bootstrap from 'bootstrap';

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

function sessionEndTimestamp(payload) {
    const explicitEnd = payload?.planned_end_at_iso || payload?.planned_end_at;
    const durationHours = Number(payload?.duration_hours || 1);

    if (explicitEnd) {
        const explicitEndTimestamp = new Date(explicitEnd).getTime();

        return Number.isNaN(explicitEndTimestamp) ? null : explicitEndTimestamp;
    }

    const base = payload?.started_at_iso || payload?.scheduled_at_iso || payload?.started_at || payload?.scheduled_at;

    if (!base) {
        return null;
    }

    return new Date(base).getTime() + (durationHours * 60 * 60 * 1000);
}

function payloadCanJoinNow(payload) {
    if (!payload) {
        return false;
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

    const scheduledAtValue = payload.scheduled_at_iso || payload.scheduled_at;

    if (!scheduledAtValue) {
        return Boolean(payload.can_join_now);
    }

    const now = Date.now();
    const scheduledAt = new Date(scheduledAtValue).getTime();

    if (Number.isNaN(scheduledAt) || now < scheduledAt) {
        return false;
    }

    if (!payload.started_at_iso && !payload.started_at && payload.join_deadline_at_iso) {
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

function joinPromptStorageKey(payload) {
    return `join-prompt-shown-${document.body.dataset.themeScope || 'app'}-${payload.id}`;
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

function applyJoinPromptPayload(prompt, payload) {
    if (!prompt) {
        return;
    }

    const normalized = normalizeJoinPayload(payload);
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

        if (sessionStorage.getItem(storageKey)) {
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

function extractErrorMessage(error, fallback) {
    const errors = error?.response?.data?.errors;

    if (errors) {
        const firstField = Object.keys(errors)[0];
        const firstMessage = firstField ? errors[firstField]?.[0] : null;

        if (firstMessage) {
            return firstMessage;
        }
    }

    return error?.response?.data?.message || error?.message || fallback;
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
        if (value === 'completed') return 'مكتملة';
        if (value === 'cancelled') return 'ملغية';
        if (value === 'in_progress') return 'جارية الآن';
        return 'قادمة';
    };

    const render = rawPayload => {
        const payload = normalizeJoinPayload(rawPayload);

        if (!payload) {
            return;
        }

        if (student) student.textContent = payload.student_name || '-';
        if (subject) subject.textContent = payload.subject_name || '-';
        if (status) status.textContent = statusLabel(payload.status);
        if (scheduled) scheduled.textContent = payload.scheduled_at || '-';
        if (notes) notes.textContent = payload.notes || 'لا توجد ملاحظات مضافة.';
        if (recording) recording.textContent = payload.recording_url ? 'متاح' : 'غير متوفر';
        if (chat) chat.textContent = payload.chat_excerpt || 'لا توجد محادثة محفوظة.';
        if (studentSummary) studentSummary.textContent = payload.student_summary_notes || 'لا توجد ملاحظات مضافة بعد.';
        if (recordingNote) recordingNote.textContent = payload.recording_note || '';

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
            joinLink.classList.toggle('disabled', !payload.join_url);
        }

        renderSessionFiles(files, payload.files, 'لا توجد ملفات مرفوعة لهذه الجلسة.');

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

    const refresh = () => {
        triggers.forEach(trigger => {
            const payload = normalizeJoinPayload(safeJsonParse(trigger.dataset.session, null));

            if (!payload) {
                return;
            }

            trigger.dataset.session = JSON.stringify(payload);

            if (trigger.classList.contains('active')) {
                render(payload);
            }
        });
    };

    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            triggers.forEach(item => item.classList.remove('active'));
            trigger.classList.add('active');
            render(safeJsonParse(trigger.dataset.session, null));
        });
    });

    const activeTrigger = Array.from(triggers).find(trigger => trigger.classList.contains('active')) || triggers[0];

    if (activeTrigger?.dataset.session) {
        render(safeJsonParse(activeTrigger.dataset.session, null));
    }

    window.setInterval(refresh, 1000);
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
        if (value === 'completed') return 'مكتملة';
        if (value === 'cancelled') return 'ملغية';
        if (value === 'in_progress') return 'جارية الآن';
        return 'قادمة';
    };

    const render = rawPayload => {
        const payload = normalizeJoinPayload(rawPayload);

        if (!payload) {
            return;
        }

        detailsRoot.style.display = '';

        if (teacher) teacher.textContent = payload.teacher_name || '-';
        if (subject) subject.textContent = payload.subject_name || '-';
        if (status) status.textContent = statusLabel(payload.status);
        if (scheduled) scheduled.textContent = payload.scheduled_at || '-';
        if (notes) notes.textContent = payload.notes || 'لا توجد ملاحظات مضافة.';
        if (recording) recording.textContent = payload.recording_url ? 'متاح' : 'غير متوفر';
        if (chat) chat.textContent = payload.chat_excerpt || 'لا توجد محادثة محفوظة.';
        if (studentConfirmed) studentConfirmed.textContent = payload.student_confirmed ? 'تم التأكيد' : 'بانتظار التأكيد';
        if (teacherConfirmed) teacherConfirmed.textContent = payload.teacher_confirmed ? 'تم التأكيد' : 'بانتظار التأكيد';
        if (studentSummary) studentSummary.textContent = payload.student_summary_notes || 'ستظهر هذه الملاحظات بعد انتهاء الجلسة.';
        if (recordingNote) recordingNote.textContent = payload.recording_note || '';

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
            joinLink.classList.toggle('disabled', !payload.join_url);
        }

        renderSessionFiles(files, payload.files, 'لا توجد ملفات مرفوعة لهذه الجلسة.');

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

    const refresh = () => {
        triggers.forEach(trigger => {
            const payload = normalizeJoinPayload(safeJsonParse(trigger.dataset.session, null));

            if (!payload) {
                return;
            }

            trigger.dataset.session = JSON.stringify(payload);

            if (trigger.classList.contains('active')) {
                render(payload);
            }
        });
    };

    triggers.forEach(trigger => {
        trigger.addEventListener('click', () => {
            triggers.forEach(item => item.classList.remove('active'));
            trigger.classList.add('active');
            render(safeJsonParse(trigger.dataset.session, null));
        });
    });

    const activeTrigger = Array.from(triggers).find(trigger => trigger.classList.contains('active')) || triggers[0];

    if (activeTrigger?.dataset.session) {
        render(safeJsonParse(activeTrigger.dataset.session, null));
    }

    window.setInterval(refresh, 1000);
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
            countNode.textContent = String(payload?.count || 0);
            countNode.classList.toggle('d-none', !payload?.count);
        }

        if (!card || !summaryNode || !metaNode || !acceptForm || !rejectForm) {
            return;
        }

        if (payload?.count > 0) {
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

    indicator._renderLiveRequests = render;
    render({ count: 0, requests: [] });

    if (!pollUrl) {
        return;
    }

    const poll = () => {
        fetch(pollUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then(response => response.json())
            .then(render)
            .catch(() => {});
    };

    poll();
    window.setInterval(poll, 20000);
}

function initializeTeacherRealtime() {
    const indicator = document.querySelector('[data-live-request-indicator]');
    const prompt = document.getElementById('teacherJoinSessionPrompt');

    if (!indicator) {
        return;
    }

    const initialPayload = safeJsonParse(indicator.dataset.activeSession, null);
    renderQuickJoinTargets('teacher', initialPayload);

    if (prompt) {
        applyJoinPromptPayload(prompt, initialPayload);
    }

    if (!window.Echo || !indicator.dataset.realtimeChannel) {
        return;
    }

    const renderLiveRequests = indicator._renderLiveRequests || (() => {});

    window.Echo.channel(indicator.dataset.realtimeChannel)
        .listen('.teacher.realtime.updated', event => {
            if (event.live_requests) {
                renderLiveRequests(event.live_requests);
            }

            const payload = event.active_session_payload || null;
            indicator.dataset.activeSession = JSON.stringify(payload);
            renderQuickJoinTargets('teacher', payload);

            if (prompt) {
                applyJoinPromptPayload(prompt, payload);
            }

            if (event.session_update) {
                const triggers = document.querySelectorAll('[data-session-trigger]');

                triggers.forEach(trigger => {
                    const currentPayload = safeJsonParse(trigger.dataset.session, null);

                    if (!currentPayload || Number(currentPayload.id) !== Number(event.session_update.id)) {
                        return;
                    }

                    trigger.dataset.session = JSON.stringify({
                        ...currentPayload,
                        ...event.session_update,
                    });

                    if (trigger.classList.contains('active')) {
                        trigger.click();
                    }
                });
            }
        });
}

function initializeStudentRealtime() {
    const root = document.querySelector('[data-student-realtime]');
    const prompt = document.getElementById('studentJoinSessionPrompt');

    if (!root) {
        return;
    }

    const initialPayload = safeJsonParse(root.dataset.activeSession, null);
    renderQuickJoinTargets('student', initialPayload);

    if (prompt) {
        applyJoinPromptPayload(prompt, initialPayload);
    }

    if (!window.Echo || !root.dataset.realtimeChannel) {
        return;
    }

    window.Echo.channel(root.dataset.realtimeChannel)
        .listen('.student.realtime.updated', event => {
            const payload = event.active_session_payload || null;
            root.dataset.activeSession = JSON.stringify(payload);
            renderQuickJoinTargets('student', payload);

            if (prompt) {
                applyJoinPromptPayload(prompt, payload);
            }

            if (event.session_update) {
                const triggers = document.querySelectorAll('[data-student-session-trigger]');

                triggers.forEach(trigger => {
                    const currentPayload = safeJsonParse(trigger.dataset.session, null);

                    if (!currentPayload || Number(currentPayload.id) !== Number(event.session_update.id)) {
                        return;
                    }

                    trigger.dataset.session = JSON.stringify({
                        ...currentPayload,
                        ...event.session_update,
                    });

                    if (trigger.classList.contains('active')) {
                        trigger.click();
                    }
                });
            }
        });
}

function initializeJoinSessionPrompts() {
    const prompts = document.querySelectorAll('[data-join-session-modal]');

    prompts.forEach(prompt => {
        const scope = prompt.id.includes('teacher') ? 'teacher' : 'student';
        const sync = () => {
            const payload = safeJsonParse(prompt.dataset.joinSessionModal, null);
            applyJoinPromptPayload(prompt, payload);
            renderQuickJoinTargets(scope, payload);
        };

        sync();
        window.setInterval(sync, 1000);
    });
}

function initializeLiveSessionRoom() {
    const root = document.querySelector('[data-live-room]');

    if (!root) {
        return;
    }

    const config = safeJsonParse(root.dataset.liveRoom, {});
    const autoJoinRequested = new URLSearchParams(window.location.search).get('autojoin') === '1';
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
    let recordingChunks = [];
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
        if (value === 'in_progress') return 'جارية الآن';
        if (value === 'completed') return 'انتهت';
        if (value === 'cancelled') return 'ملغية';
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

    const currentRoomCanJoin = () => payloadCanJoinNow({
        status: roomState?.session?.status,
        scheduled_at: roomState?.session?.scheduled_at,
        started_at: roomState?.session?.started_at,
        planned_end_at: roomState?.session?.planned_end_at,
        join_deadline_at_iso: roomState?.session?.join_deadline_at,
        duration_hours: roomState?.session?.duration_hours,
        can_join_now: roomState?.session?.can_join_now,
    });

    const updateJoinButtons = canJoin => {
        joinButtons.forEach(button => {
            button.disabled = !canJoin || joining;
            button.classList.toggle('d-none', joined || !canJoin);
        });
    };

    const setRoomFormsEnabled = enabled => {
        [chatForm, fileForm, complaintForm].forEach(form => {
            if (!form) {
                return;
            }

            form.querySelectorAll('input, textarea, button').forEach(element => {
                element.disabled = !enabled;
            });
        });
    };

    const mergeCollectionItem = (items, payload, prepend = false) => {
        const nextItems = Array.isArray(items) ? [...items] : [];

        if (nextItems.some(item => Number(item.id) === Number(payload.id))) {
            return nextItems;
        }

        if (prepend) {
            nextItems.unshift(payload);
            return nextItems;
        }

        nextItems.push(payload);
        return nextItems;
    };

    const renderTimer = () => {
        if (!remainingNode) {
            return;
        }

        if (!roomState?.session?.started_at) {
            remainingNode.textContent = '--:--:--';

            if (endingSoon) {
                endingSoon.classList.add('d-none');
            }

            return;
        }

        const endAt = sessionEndTimestamp(roomState.session);

        if (!endAt) {
            remainingNode.textContent = '--:--:--';
            return;
        }

        const diff = endAt - Date.now();

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

        if (!window.RTCPeerConnection) {
            throw new Error('متصفحك لا يدعم البث المباشر.');
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

            remoteVideo?.play().catch(() => {});
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

        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('هذا المتصفح لا يدعم الوصول إلى الكاميرا أو الميكروفون.');
        }

        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        } catch (error) {
            if (error?.name === 'NotAllowedError') {
                throw new Error('تم رفض الوصول إلى الكاميرا أو الميكروفون. اسمح بالوصول ثم أعد المحاولة.');
            }

            if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
                throw new Error('لا توجد كاميرا أو ميكروفون متاحان على هذا الجهاز، لذلك لن يكتمل الانضمام للجلسة المباشرة.');
            }

            throw new Error('تعذر تشغيل الكاميرا أو الميكروفون على هذا الجهاز حاليًا.');
        }

        if (localVideo) {
            localVideo.srcObject = localStream;
            localVideo.play().catch(() => {});
        }

        return localStream;
    };

    const sendSignal = async (signalType, payload) => {
        await window.axios.post(config.signalUrl, {
            signal_type: signalType,
            payload,
        });
    };

    const createOfferIfNeeded = async () => {
        if (config.role !== 'teacher' || offerSent || !joined) {
            return;
        }

        if (!roomState?.session?.teacher_joined_at || !roomState?.session?.student_joined_at) {
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
        if (!joined) {
            return;
        }

        await ensureLocalMedia();
        const pc = await ensurePeerConnection();

        if (signal.signal_type === 'offer') {
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
                // Ignore late candidates that race with teardown.
            }
        }
    };

    const startRecordingIfPossible = () => {
        if (mediaRecorder || !localStream || remoteStream.getTracks().length === 0 || !window.MediaRecorder) {
            return;
        }

        const combined = new MediaStream([
            ...localStream.getTracks(),
            ...remoteStream.getTracks(),
        ]);

        const mimeType = recordingMimeType();
        mediaRecorder = mimeType
            ? new MediaRecorder(combined, { mimeType })
            : new MediaRecorder(combined);

        recordingChunks = [];

        mediaRecorder.ondataavailable = event => {
            if (event.data?.size) {
                recordingChunks.push(event.data);
            }
        };

        mediaRecorder.start(1000);
    };

    const stopRecordingAndUpload = async () => {
        if (!mediaRecorder || mediaRecorder.state === 'inactive') {
            return;
        }

        await new Promise(resolve => {
            mediaRecorder.onstop = async () => {
                if (recordingChunks.length) {
                    const blob = new Blob(recordingChunks, { type: mediaRecorder.mimeType || 'video/webm' });
                    const formData = new FormData();
                    formData.append('recording', blob, `session-${config.sessionId}.webm`);

                    try {
                        await window.axios.post(config.recordingUrl, formData);
                    } catch (error) {
                        // Ignore upload failures to keep the room responsive.
                    }
                }

                resolve();
            };

            mediaRecorder.stop();
        });
    };

    const renderState = async payload => {
        roomState = payload;

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
        updateJoinButtons(currentRoomCanJoin());
        setRoomFormsEnabled(payload.session.status === 'in_progress');

        if (payload.session.status === 'completed' || payload.session.status === 'cancelled') {
            updateOverlay(payload.session.status === 'completed' ? 'انتهت الجلسة وتم حفظ بياناتها.' : 'تم إلغاء الجلسة.');
            await stopRecordingAndUpload();

            if (!redirecting) {
                redirecting = true;
                window.setTimeout(() => {
                    window.location.href = config.redirectUrl;
                }, 1500);
            }

            return;
        }

        if (payload.session.teacher_joined_at && payload.session.student_joined_at && joined) {
            await createOfferIfNeeded();
        }

        if (currentRoomCanJoin() && !joined) {
            updateOverlay('يمكنك الانضمام الآن. سيتم تشغيل الكاميرا والميكروفون بعد الموافقة.');
        } else if (joined && remoteStream.getTracks().length === 0) {
            updateOverlay('تم تجهيز جلستك. بانتظار الطرف الآخر أو استكمال الاتصال المباشر.');
        } else if (!payload.session.started_at) {
            updateOverlay('سيتم تفعيل الجلسة فور حلول الموعد ودخول الطرفين.');
        } else {
            updateOverlay('');
        }

        for (const signal of payload.signals || []) {
            if (!joined) {
                continue;
            }

            await handleSignal(signal);
            lastSignalId = Math.max(lastSignalId, Number(signal.id || 0));
        }

        renderTimer();
    };

    const pollState = async () => {
        const response = await window.axios.get(config.stateUrl, {
            params: { last_signal_id: lastSignalId },
        });

        await renderState(response.data);
    };

    const joinRoom = async () => {
        if (joining || joined) {
            return;
        }

        joining = true;
        updateJoinButtons(false);

        try {
            await ensureLocalMedia();
            await window.axios.post(config.joinUrl);
            joined = true;
            offerSent = false;
            await ensurePeerConnection();
            await pollState();

            if (roomState?.session?.teacher_joined_at && roomState?.session?.student_joined_at) {
                await createOfferIfNeeded();
            }
        } catch (error) {
            joined = false;
            updateOverlay(extractErrorMessage(error, 'تعذر الانضمام إلى الجلسة الآن. حاول مجددًا بعد لحظات.'));
            throw error;
        } finally {
            joining = false;
            updateJoinButtons(currentRoomCanJoin());
        }
    };

    const handleRoomEvent = async event => {
        roomState = {
            ...roomState,
            session: {
                ...roomState.session,
                ...(event.session || {}),
            },
        };

        if (event.type === 'signal') {
            if (joined && event.payload) {
                await handleSignal(event.payload);
                lastSignalId = Math.max(lastSignalId, Number(event.payload.id || 0));
            }

            return;
        }

        if (event.type === 'message' && event.payload) {
            roomState.messages = mergeCollectionItem(roomState.messages, event.payload);
            renderMessages(roomState.messages);
            return;
        }

        if (event.type === 'file' && event.payload) {
            roomState.files = mergeCollectionItem(roomState.files, event.payload, true);
            renderSessionFiles(fileList, roomState.files, 'لا توجد ملفات مرفوعة بعد.');
            return;
        }

        if (event.type === 'complaint' && event.payload) {
            roomState.complaints = mergeCollectionItem(roomState.complaints, event.payload, true);
            renderComplaints(roomState.complaints);
            return;
        }

        await pollState();
    };

    const submitEndRequest = async confirmEnd => {
        if (ending) {
            return;
        }

        ending = true;

        try {
            const response = await window.axios.post(config.endUrl, { confirm_end: confirmEnd });
            await stopRecordingAndUpload();
            window.location.href = response.data.redirect_url;
        } catch (error) {
            const confirmMessage = error?.response?.data?.errors?.confirm_end?.[0];

            if (confirmMessage && endConfirmMessage) {
                endConfirmMessage.textContent = confirmMessage;
                endModal?.show();
                return;
            }

            updateOverlay(extractErrorMessage(error, 'تعذر إنهاء الجلسة الآن. حاول مجددًا.'));
            throw error;
        } finally {
            ending = false;
        }
    };

    const endSession = async () => {
        const startedAtValue = roomState?.session?.started_at;
        const startedAt = startedAtValue ? new Date(startedAtValue) : null;
        const elapsedMinutes = startedAt ? Math.floor((Date.now() - startedAt.getTime()) / 60000) : 0;

        if (elapsedMinutes < 15) {
            await submitEndRequest(false);
            return;
        }

        if (endConfirmMessage) {
            endConfirmMessage.textContent = config.role === 'student'
                ? 'إنهاء الجلسة الآن يعني احتساب ثمنها كاملًا عليك. هل تريد المتابعة؟'
                : 'هل أنت متأكد من إنهاء الجلسة الآن؟';
        }

        endModal?.show();
    };

    joinButtons.forEach(button => {
        button.addEventListener('click', () => {
            joinRoom().catch(() => {});
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

            if (roomState?.session?.status !== 'in_progress') {
                updateOverlay('الشات يتفعل فقط بعد بدء الجلسة فعليًا ودخول الطرفين.');
                return;
            }

            const formData = new FormData(chatForm);
            const message = String(formData.get('message') || '').trim();

            if (!message) {
                return;
            }

            try {
                await window.axios.post(config.messageUrl, { message });
                chatForm.reset();

                if (!window.Echo) {
                    await pollState();
                }
            } catch (error) {
                updateOverlay(extractErrorMessage(error, 'تعذر إرسال الرسالة الآن.'));
            }
        });
    }

    if (fileForm) {
        fileForm.addEventListener('submit', async event => {
            event.preventDefault();
            const formData = new FormData(fileForm);

            if (!formData.get('file')) {
                return;
            }

            await window.axios.post(config.fileUrl, formData);
            fileForm.reset();

            if (!window.Echo) {
                await pollState();
            }
        });
    }

    if (complaintForm) {
        complaintForm.addEventListener('submit', async event => {
            event.preventDefault();

            const formData = new FormData(complaintForm);
            await window.axios.post(config.complaintUrl, {
                title: String(formData.get('title') || '').trim(),
                description: String(formData.get('description') || '').trim(),
            });
            complaintForm.reset();

            if (!window.Echo) {
                await pollState();
            }
        });
    }

    const scheduleNotesSave = () => {
        if (!notesStatus || !config.notesUrl) {
            return;
        }

        notesStatus.textContent = 'جاري تجهيز الحفظ...';
        window.clearTimeout(notesTimer);
        notesTimer = window.setTimeout(async () => {
            await window.axios.post(config.notesUrl, {
                teacher_private_notes: teacherNotes?.value || '',
                student_summary_notes: studentNotes?.value || '',
            });

            notesStatus.textContent = 'تم الحفظ.';
        }, 700);
    };

    teacherNotes?.addEventListener('input', scheduleNotesSave);
    studentNotes?.addEventListener('input', scheduleNotesSave);
    endButton?.addEventListener('click', () => {
        endSession().catch(() => {});
    });

    endConfirmSubmit?.addEventListener('click', async () => {
        endModal?.hide();
        await submitEndRequest(true);
    });

    if (window.Echo && config.roomChannel) {
        window.Echo.channel(config.roomChannel)
            .listen('.live-session.event', event => {
                handleRoomEvent(event).catch(() => {});
            });
    }

    window.setInterval(renderTimer, 1000);
    window.setInterval(() => {
        pollState().catch(() => {});
    }, 15000);

    renderState(roomState).catch(() => {});

    if (autoJoinRequested && currentRoomCanJoin()) {
        joinRoom().catch(() => {});
    } else if (currentRoomCanJoin()) {
        updateOverlay('يمكنك الانضمام الآن. اضغط زر الانضمام لبدء الجلسة.');
    } else {
        updateOverlay('سيتم تفعيل الانضمام بمجرد حلول موعد الجلسة وتأكيد الطرفين للحضور.');
    }

    window.addEventListener('beforeunload', () => {
        localStream?.getTracks().forEach(track => track.stop());
        peerConnection?.close();
    });
}

window.Ba3eedOverrides = {
    initializeTeacherSessionPanel,
    initializeStudentSessionPanel,
    initializeTeacherLivePolling,
    initializeTeacherRealtime,
    initializeStudentRealtime,
    initializeJoinSessionPrompts,
    initializeLiveSessionRoom,
};
