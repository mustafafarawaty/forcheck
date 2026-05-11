<template>
    <div class="video-call-shell">
        <header class="video-call-header">
            <div>
                <div class="video-call-kicker">جلسة مباشرة</div>
                <h1 class="video-call-title">{{ titleText }}</h1>
                <p class="video-call-meta">
                    مع {{ participantText }}
                </p>
            </div>

            <div class="video-call-header-actions">
                <div class="video-call-status" :data-status="sessionStatus">
                    {{ statusText }}
                </div>
                <a :href="config.redirectUrl || '#'" class="video-call-back">
                    رجوع للجلسات
                </a>
            </div>
        </header>

        <section class="video-call-stage">
            <div class="video-call-stage-topbar">
                <div class="video-call-badge">
                    <span class="video-call-dot"></span>
                    <span>مكالمة صوت وصورة عبر Agora</span>
                </div>

                <div class="video-call-timer">
                    <span class="video-call-timer-label">الوقت المتبقي</span>
                    <strong>{{ remainingText }}</strong>
                </div>
            </div>

            <div ref="remoteContainer" class="video-call-remote"></div>

            <div v-if="overlayMessage" class="video-call-overlay">
                <p>{{ overlayMessage }}</p>
            </div>

            <div ref="localContainer" class="video-call-local"></div>

            <div class="video-call-controls">
                <button
                    type="button"
                    class="video-call-button"
                    :class="{ 'is-off': !micOn }"
                    :disabled="!joinedAgora"
                    @click="toggleMic"
                >
                    {{ micOn ? 'إغلاق المايك' : 'تشغيل المايك' }}
                </button>

                <button
                    type="button"
                    class="video-call-button"
                    :class="{ 'is-off': !cameraOn }"
                    :disabled="!joinedAgora"
                    @click="toggleCamera"
                >
                    {{ cameraOn ? 'إغلاق الكاميرا' : 'تشغيل الكاميرا' }}
                </button>

                <button
                    v-if="!joinedAgora"
                    type="button"
                    class="video-call-button is-primary"
                    :disabled="joining || !canJoinNow"
                    @click="startCall"
                >
                    {{ joining ? 'جار تجهيز المكالمة...' : 'الانضمام الآن' }}
                </button>

                <button
                    type="button"
                    class="video-call-button is-danger"
                    :disabled="ending || !canEndNow"
                    @click="endSession"
                >
                    {{ ending ? 'جار إنهاء الجلسة...' : 'إنهاء الجلسة' }}
                </button>
            </div>

            <div v-if="showEndingWarning" class="video-call-warning">
                تبقّى أقل من 10 دقائق على نهاية الجلسة.
            </div>
        </section>
    </div>
</template>

<script setup>
import AgoraRTC from 'agora-rtc-sdk-ng';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
});

const roomState = ref(props.config.initialState || { session: {} });
const remoteContainer = ref(null);
const localContainer = ref(null);
const joinedAgora = ref(false);
const joining = ref(false);
const ending = ref(false);
const micOn = ref(true);
const cameraOn = ref(true);
const remoteAudioReady = ref(false);
const remoteVideoReady = ref(false);
const remainingText = ref('--:--:--');
const showEndingWarning = ref(false);
const redirecting = ref(false);
const countdownStarted = ref(false);
const joinError = ref('');

let client = null;
let localTracks = [];
let roomChannel = null;
let pollTimer = null;
let countdownTimer = null;
let joinSucceeded = false;

const session = computed(() => roomState.value?.session || {});
const sessionStatus = computed(() => session.value.status || 'upcoming');
const canJoinNow = computed(() => Boolean(session.value.can_join_now));
const canEndNow = computed(() => sessionStatus.value === 'in_progress');
const bothParticipantsMarkedJoined = computed(() => Boolean(session.value.teacher_joined_at && session.value.student_joined_at));
const callConnected = computed(() => joinedAgora.value && bothParticipantsMarkedJoined.value && remoteAudioReady.value && remoteVideoReady.value);
const titleText = computed(() => session.value.subject_name || 'جلسة تعليمية');
const participantText = computed(() => {
    if (props.config.role === 'teacher') {
        return session.value.student_name || 'الطالب';
    }

    return session.value.teacher_name || 'الأستاذ';
});
const statusText = computed(() => {
    if (sessionStatus.value === 'in_progress') {
        return 'جارية الآن';
    }

    if (sessionStatus.value === 'completed') {
        return 'انتهت';
    }

    if (sessionStatus.value === 'cancelled') {
        return 'ملغية';
    }

    return 'قيد الانتظار';
});
const overlayMessage = computed(() => {
    if (joinError.value) {
        return joinError.value;
    }

    if (sessionStatus.value === 'completed') {
        return 'انتهت الجلسة وتم فصل الاتصال للطرفين.';
    }

    if (sessionStatus.value === 'cancelled') {
        return 'تم إلغاء هذه الجلسة.';
    }

    if (joining.value) {
        return 'جار تجهيز الكاميرا والميكروفون والاتصال بالغرفة...';
    }

    if (!canJoinNow.value && !joinedAgora.value) {
        return 'سيظهر زر الانضمام فور حلول موعد الجلسة وتأكيد الطرفين.';
    }

    if (joinedAgora.value && !callConnected.value && !countdownStarted.value) {
        return 'تم دخولك للجلسة. بانتظار اكتمال اتصال الطرف الآخر صوتاً وصورة.';
    }

    if (!joinedAgora.value && canJoinNow.value) {
        return 'حان وقت الجلسة. يمكنك الانضمام الآن.';
    }

    return '';
});

function normalizeBroadcastSession(payload = {}) {
    return {
        ...session.value,
        ...payload,
    };
}

function renderCountdown() {
    if (callConnected.value) {
        countdownStarted.value = true;
    }

    if (!countdownStarted.value) {
        remainingText.value = '--:--:--';
        showEndingWarning.value = false;

        return;
    }

    const plannedEndAt = session.value.planned_end_at;

    if (!plannedEndAt) {
        remainingText.value = '--:--:--';
        showEndingWarning.value = false;

        return;
    }

    const diff = new Date(plannedEndAt).getTime() - Date.now();

    if (diff <= 0) {
        remainingText.value = '00:00:00';
        showEndingWarning.value = true;

        if (canEndNow.value && !ending.value) {
            void requestEnd(true);
        }

        return;
    }

    const totalSeconds = Math.floor(diff / 1000);
    const hours = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');

    remainingText.value = `${hours}:${minutes}:${seconds}`;
    showEndingWarning.value = diff <= 10 * 60 * 1000;
}

async function pollState() {
    const response = await axios.get(props.config.stateUrl);

    roomState.value = response.data;

    if (['completed', 'cancelled'].includes(roomState.value?.session?.status || '')) {
        await finalizeAndRedirect();
        return;
    }

    renderCountdown();
}

function ensureClient() {
    if (client) {
        return client;
    }

    client = AgoraRTC.createClient({
        mode: 'rtc',
        codec: 'vp8',
    });

    client.on('user-published', handleUserPublished);
    client.on('user-unpublished', handleRemoteStateChanged);
    client.on('user-left', handleRemoteStateChanged);

    return client;
}

async function fetchAgoraToken() {
    const response = await axios.post(props.config.agoraTokenUrl);

    return response.data;
}

async function ensureLocalTracks() {
    if (localTracks.length) {
        return localTracks;
    }

    localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();

    if (localContainer.value && localTracks[1]) {
        localTracks[1].play(localContainer.value);
    }

    return localTracks;
}

function clearRemoteContainer() {
    if (remoteContainer.value) {
        remoteContainer.value.innerHTML = '';
    }

    remoteAudioReady.value = false;
    remoteVideoReady.value = false;
}

function handleRemoteStateChanged() {
    const remoteUsers = client?.remoteUsers || [];

    remoteAudioReady.value = remoteUsers.some(user => Boolean(user.audioTrack));
    remoteVideoReady.value = remoteUsers.some(user => Boolean(user.videoTrack));

    if (!remoteVideoReady.value) {
        clearRemoteContainer();
    }

    renderCountdown();
}

async function handleUserPublished(user, mediaType) {
    const activeClient = ensureClient();

    await activeClient.subscribe(user, mediaType);

    if (mediaType === 'audio' && user.audioTrack) {
        user.audioTrack.play();
        remoteAudioReady.value = true;
        renderCountdown();
        return;
    }

    if (mediaType === 'video' && user.videoTrack && remoteContainer.value) {
        remoteContainer.value.innerHTML = '';

        const player = document.createElement('div');
        player.className = 'video-call-remote-player';
        remoteContainer.value.appendChild(player);
        user.videoTrack.play(player);
        remoteVideoReady.value = true;
        renderCountdown();
    }
}

async function startCall() {
    if (joining.value || joinedAgora.value) {
        return;
    }

    joining.value = true;
    joinError.value = '';
    joinSucceeded = false;

    try {
        const activeClient = ensureClient();
        const credentials = await fetchAgoraToken();

        await activeClient.join(
            credentials.appId || props.config.appId,
            credentials.channel,
            credentials.token,
            null,
        );

        const tracks = await ensureLocalTracks();
        await activeClient.publish(tracks);
        await axios.post(props.config.joinUrl);

        joinedAgora.value = true;
        joinSucceeded = true;
        void pollState().catch(() => { });
    } catch (error) {
        joinError.value = error?.response?.data?.errors?.session?.[0]
            || error?.response?.data?.message
            || 'تعذر الانضمام إلى المكالمة الآن. حاول مرة أخرى بعد قليل.';
        await disconnectAgora();
    } finally {
        joining.value = false;
    }
}

async function disconnectAgora() {
    try {
        if (client && localTracks.length && joinSucceeded) {
            await client.unpublish(localTracks);
        }
    } catch (error) {
        // Ignore teardown race conditions.
    }

    localTracks.forEach(track => {
        try {
            track.stop();
            track.close();
        } catch (error) {
            // Ignore close failures during teardown.
        }
    });

    localTracks = [];

    try {
        if (client) {
            await client.leave();
        }
    } catch (error) {
        // Ignore leave failures during teardown.
    }

    if (localContainer.value) {
        localContainer.value.innerHTML = '';
    }

    clearRemoteContainer();
    joinedAgora.value = false;
    micOn.value = true;
    cameraOn.value = true;
    joinSucceeded = false;
}

async function finalizeAndRedirect() {
    if (redirecting.value) {
        return;
    }

    redirecting.value = true;
    await disconnectAgora();

    window.setTimeout(() => {
        window.location.href = props.config.redirectUrl || '/';
    }, 700);
}

async function requestEnd(confirmEnd) {
    if (ending.value) {
        return;
    }

    ending.value = true;

    try {
        const response = await axios.post(props.config.endUrl, {
            confirm_end: confirmEnd,
        });

        roomState.value = {
            ...roomState.value,
            session: normalizeBroadcastSession(response.data.session || {}),
        };

        await finalizeAndRedirect();
    } finally {
        ending.value = false;
    }
}

async function endSession() {
    const startedAt = session.value.started_at ? new Date(session.value.started_at) : null;
    const elapsedMinutes = startedAt ? Math.floor((Date.now() - startedAt.getTime()) / 60000) : 0;

    if (elapsedMinutes >= 15) {
        const message = props.config.role === 'student'
            ? 'إنهاء الجلسة الآن سيؤدي إلى احتساب قيمتها كاملة. هل تريد المتابعة؟'
            : 'هل أنت متأكد من إنهاء الجلسة الآن؟';

        if (!window.confirm(message)) {
            return;
        }
    }

    await requestEnd(elapsedMinutes >= 15);
}

function toggleMic() {
    if (!localTracks[0]) {
        return;
    }

    micOn.value = !micOn.value;
    void localTracks[0].setEnabled(micOn.value);
}

function toggleCamera() {
    if (!localTracks[1]) {
        return;
    }

    cameraOn.value = !cameraOn.value;
    void localTracks[1].setEnabled(cameraOn.value);

    if (!cameraOn.value && localContainer.value) {
        localContainer.value.innerHTML = '';
        return;
    }

    if (cameraOn.value && localContainer.value) {
        localTracks[1].play(localContainer.value);
    }
}

function subscribeToRoomChannel() {
    if (!window.Echo || !props.config.roomChannel) {
        return;
    }

    roomChannel = window.Echo.channel(props.config.roomChannel);
    roomChannel.listen('.live-session.event', async event => {
        roomState.value = {
            ...roomState.value,
            session: normalizeBroadcastSession(event?.session || {}),
        };

        if (['completed', 'cancelled'].includes(roomState.value?.session?.status || '')) {
            await finalizeAndRedirect();
            return;
        }

        if (event?.type === 'state' || event?.type === 'ended') {
            await pollState();
        }
    });
}

onMounted(async () => {
    subscribeToRoomChannel();
    renderCountdown();

    countdownTimer = window.setInterval(renderCountdown, 1000);
    pollTimer = window.setInterval(() => {
        void pollState();
    }, 10000);

    await pollState();
});

onBeforeUnmount(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
    }

    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }

    if (roomChannel && props.config.roomChannel && window.Echo) {
        window.Echo.leave(props.config.roomChannel);
    }

    void disconnectAgora();
});
</script>

<style scoped>
.video-call-shell {
    display: grid;
    gap: 1.5rem;
}

.video-call-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}

.video-call-kicker {
    color: #0f766e;
    font-size: 0.82rem;
    font-weight: 700;
}

.video-call-title {
    margin: 0.35rem 0 0.4rem;
    font-size: clamp(1.5rem, 2vw, 2rem);
    font-weight: 800;
    color: #0f172a;
}

.video-call-meta {
    margin: 0;
    color: #475569;
}

.video-call-header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.video-call-status {
    padding: 0.55rem 0.9rem;
    border-radius: 999px;
    background: #e2e8f0;
    color: #0f172a;
    font-weight: 700;
}

.video-call-status[data-status='in_progress'] {
    background: #dcfce7;
    color: #166534;
}

.video-call-status[data-status='completed'] {
    background: #dbeafe;
    color: #1d4ed8;
}

.video-call-status[data-status='cancelled'] {
    background: #fee2e2;
    color: #b91c1c;
}

.video-call-back {
    color: #0f766e;
    font-weight: 700;
    text-decoration: none;
}

.video-call-stage {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 1rem;
    background:
        radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 28%),
        linear-gradient(145deg, #0f172a, #111827 55%, #1e293b);
    min-height: 72vh;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.22);
}

.video-call-stage-topbar {
    position: relative;
    z-index: 3;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.video-call-badge,
.video-call-timer {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.8rem 1rem;
    border-radius: 18px;
    background: rgba(15, 23, 42, 0.5);
    color: #f8fafc;
    backdrop-filter: blur(14px);
}

.video-call-dot {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
    background: #ef4444;
    box-shadow: 0 0 0 0.3rem rgba(239, 68, 68, 0.2);
}

.video-call-timer {
    flex-direction: column;
    align-items: flex-start;
}

.video-call-timer-label {
    font-size: 0.8rem;
    color: rgba(248, 250, 252, 0.72);
}

.video-call-timer strong {
    font-size: 1.6rem;
    letter-spacing: 0.08em;
}

.video-call-remote {
    position: relative;
    min-height: calc(72vh - 8rem);
    border-radius: 22px;
    background: #020617;
    overflow: hidden;
}

.video-call-remote :deep(video),
.video-call-remote :deep(.agora_video_player),
.video-call-local :deep(video),
.video-call-local :deep(.agora_video_player) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}

.video-call-remote-player {
    width: 100%;
    height: 100%;
}

.video-call-overlay {
    position: absolute;
    inset: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    background: rgba(2, 6, 23, 0.44);
    color: #f8fafc;
}

.video-call-overlay p {
    max-width: 28rem;
    margin: 0;
    font-size: 1rem;
    line-height: 1.8;
}

.video-call-local {
    position: absolute;
    right: 1.4rem;
    bottom: 6.2rem;
    z-index: 3;
    width: min(26vw, 220px);
    aspect-ratio: 3 / 4;
    border: 2px solid rgba(255, 255, 255, 0.82);
    border-radius: 18px;
    overflow: hidden;
    background: #020617;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35);
}

.video-call-controls {
    position: absolute;
    left: 50%;
    bottom: 1.2rem;
    z-index: 3;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
    transform: translateX(-50%);
    width: calc(100% - 2rem);
}

.video-call-button {
    border: 0;
    border-radius: 999px;
    padding: 0.95rem 1.3rem;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 700;
    backdrop-filter: blur(14px);
    cursor: pointer;
}

.video-call-button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.video-call-button.is-primary {
    background: #14b8a6;
}

.video-call-button.is-danger,
.video-call-button.is-off {
    background: #dc2626;
}

.video-call-warning {
    position: absolute;
    top: 5.5rem;
    left: 1rem;
    z-index: 3;
    padding: 0.8rem 1rem;
    border-radius: 16px;
    background: rgba(245, 158, 11, 0.18);
    color: #fef3c7;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

@media (max-width: 768px) {
    .video-call-stage {
        min-height: 78vh;
        padding: 0.8rem;
    }

    .video-call-stage-topbar {
        margin-bottom: 0.8rem;
    }

    .video-call-remote {
        min-height: 58vh;
    }

    .video-call-local {
        width: min(34vw, 148px);
        right: 1rem;
        bottom: 7.4rem;
    }

    .video-call-controls {
        bottom: 1rem;
    }

    .video-call-button {
        width: calc(50% - 0.5rem);
        text-align: center;
        padding: 0.85rem 0.9rem;
    }

    .video-call-warning {
        top: auto;
        bottom: 13rem;
        left: 0.8rem;
        right: 0.8rem;
    }
}
</style>
