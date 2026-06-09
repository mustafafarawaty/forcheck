<template>
    <div class="call-container" :class="{ 'solo-mode': joined && !hasRemote }">
        <div id="remote-player" class="remote-video"></div>
        <div id="local-player" class="local-video"></div>

        <div class="session-timer">
            <span class="timer-display">{{ formattedTime }}</span>
        </div>

        <div class="controls">
            <button type="button" @click="toggleMic" :class="{ off: !micOn }">Mic</button>
            <button type="button" @click="toggleCamera" :class="{ off: !cameraOn }">Cam</button>
            <button type="button" class="leave" @click="handleLeaveClick">End</button>
            <button type="button" class="chat-btn" @click="toggleChat">
                Chat
                <span v-if="chatUnread" class="badge">{{ chatUnread }}</span>
            </button>
        </div>

        <button v-if="!joined && canJoinNow" class="join-btn" type="button" :disabled="joining" @click="startCall">
            {{ joining ? 'Joining...' : 'Join now' }}
        </button>

        <div class="chat-panel" :class="{ open: chatOpen }">
            <div class="chat-header">
                <span>Chat</span>
                <button type="button" @click="toggleChat">✕</button>
            </div>

            <div ref="chatBody" class="chat-body">
                <div
                    v-for="message in chatMessages"
                    :key="message.id || `${message.sender_role}-${message.created_at}-${message.message}`"
                    class="msg"
                    :class="{ mine: message.sender_role === config.role }"
                >
                    <div class="bubble">
                        <div class="name">{{ message.sender_name }}</div>
                        <div class="text">{{ message.message }}</div>
                    </div>
                </div>
            </div>

            <div class="chat-input">
                <input
                    v-model="chatMessageText"
                    type="text"
                    placeholder="Type a message..."
                    @keyup.enter="sendMessage"
                />
                <button type="button" :disabled="chatSending" @click="sendMessage">Send</button>
            </div>
        </div>

        <div v-if="showLeaveDialog" class="leave-dialog-backdrop">
            <div class="leave-dialog">
                <h3>{{ leaveDialogTitle }}</h3>
                <p>{{ leaveDialogHint }}</p>
                <textarea
                    v-if="isEarlyLeave"
                    v-model="leaveReason"
                    rows="4"
                    placeholder="اكتب سبب الإلغاء إذا أردت"
                ></textarea>
                <div class="leave-dialog-actions">
                    <button type="button" class="ghost" @click="showLeaveDialog = false">متابعة الجلسة</button>
                    <button type="button" class="leave" :disabled="ending" @click="confirmLeave">
                        {{ ending ? 'جاري المعالجة...' : leaveDialogActionLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AgoraRTC from 'agora-rtc-sdk-ng';
import axios from 'axios';

const ROOM_EVENT_TYPES_REQUIRING_SYNC = ['state', 'ended', 'file', 'complaint', 'recording'];

const normalizeChatMessages = (messages = []) => {
    const byId = new Map();
    const withoutId = [];

    messages.forEach(message => {
        if (!message?.message) {
            return;
        }

        const messageId = Number(message.id || 0);

        if (messageId) {
            byId.set(messageId, {
                ...byId.get(messageId),
                ...message,
            });
            return;
        }

        withoutId.push(message);
    });

    return [
        ...Array.from(byId.values()),
        ...withoutId,
    ].sort((first, second) => Number(first.id || 0) - Number(second.id || 0));
};

export default {
    props: {
        config: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        const initialMessages = Array.isArray(this.config.initialState?.messages)
            ? this.config.initialState.messages
            : [];

        return {
            client: null,
            localTracks: [],
            remoteAudioTrack: null,
            remoteVideoTrack: null,
            token: null,
            channel: '',
            joined: false,
            joining: false,
            ending: false,
            redirecting: false,
            hasRemote: false,
            micOn: true,
            cameraOn: true,
            timeRemaining: null,
            timerInterval: null,
            stateInterval: null,
            zeroHandled: false,
            sessionState: this.config.initialState?.session || null,
            serverTimeOffsetMs: 0,
            chatOpen: false,
            chatMessages: normalizeChatMessages(initialMessages),
            chatMessageText: '',
            chatUnread: 0,
            chatSending: false,
            showLeaveDialog: false,
            leaveReason: '',
            mediaRecorder: null,
            recordingChunks: [],
            recordingStatus: 'idle',
            recordingSaved: false,
            recordingStopPromise: null,
            recordingCanvas: null,
            recordingCanvasCtx: null,
            recordingCanvasStream: null,
            recordingAudioContext: null,
            recordingAudioDestination: null,
            recordingAudioSources: {},
            recordingRenderFrameId: null,
        };
    },

    computed: {
        formattedTime() {
            if (this.timeRemaining === null) {
                return '--:--:--';
            }

            if (this.timeRemaining <= 0) {
                return '00:00:00';
            }

            const hours = Math.floor(this.timeRemaining / 3600);
            const minutes = Math.floor((this.timeRemaining % 3600) / 60);
            const seconds = this.timeRemaining % 60;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        },

        currentStatus() {
            return this.sessionState?.status || this.config.sessionStatus || 'upcoming';
        },

        canJoinNow() {
            if (typeof this.sessionState?.can_join_now === 'boolean') {
                return this.sessionState.can_join_now;
            }

            return this.currentStatus === 'upcoming' || this.currentStatus === 'in_progress';
        },

        recordingStatusLabel() {
            return 'معلقين التسجيل حاليا';
        },

        isEarlyLeave() {
            const endMs = this.countdownEndMs();

            return Boolean(endMs && this.correctedNowMs() < endMs);
        },

        leaveDialogTitle() {
            return this.isEarlyLeave ? 'إلغاء الجلسة' : 'إنهاء الجلسة';
        },

        leaveDialogHint() {
            return this.isEarlyLeave
                ? 'سيتم إلغاء الجلسة وسيبقى الرصيد معلقاً بانتظار مراجعة الإدارة.'
                : 'سيتم إنهاء الجلسة الحالية الآن.';
        },

        leaveDialogActionLabel() {
            return this.isEarlyLeave ? 'تأكيد الإلغاء' : 'تأكيد الإنهاء';
        },
    },

    created() {
        this.channel = this.config.agoraChannel || `session-${this.config.sessionId}`;
    },

    mounted() {
        if (!this.supportsRecording()) {
            this.recordingStatus = 'unsupported';
        }

        this.syncServerTime(this.config.serverNowTs || this.config.initialState?.server_now_ts || null);
        this.scrollChatToBottom();
        this.startTimer();
        this.startStatePolling();
        this.initRoomChannel();
        window.addEventListener('beforeunload', this.handleBeforeUnload);

        if (this.config.autojoin && this.canJoinNow) {
            this.startCall();
        }
    },

    beforeUnmount() {
        this.stopTimer();
        this.stopStatePolling();
        this.cleanupRecordingResources();

        if (window.Echo && this.config.realtimeChannel) {
            window.Echo.leaveChannel(this.config.realtimeChannel);
        }

        window.removeEventListener('beforeunload', this.handleBeforeUnload);
        this.performLocalLeave();
    },

    methods: {
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        normalizeMessages(messages = []) {
            return normalizeChatMessages(messages);
        },

        syncMessages(messages = []) {
            this.chatMessages = this.normalizeMessages(messages);

            if (this.chatOpen) {
                this.scrollChatToBottom();
            }
        },

        mergeMessage(message) {
            if (!message?.message) {
                return;
            }

            this.chatMessages = this.normalizeMessages([
                ...this.chatMessages,
                message,
            ]);

            if (!this.chatOpen && message.sender_role !== this.config.role) {
                this.chatUnread++;
            }

            this.scrollChatToBottom();
        },

        scrollChatToBottom() {
            this.$nextTick(() => {
                const chatBody = this.$refs.chatBody;

                if (chatBody) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            });
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        supportsRecording() {
            return false;
        },

        recordingMimeType() {
            if (!window.MediaRecorder?.isTypeSupported) {
                return '';
            }

            const candidates = [
                'video/webm;codecs=vp9,opus',
                'video/webm;codecs=vp8,opus',
                'video/webm',
            ];

            return candidates.find(type => MediaRecorder.isTypeSupported(type)) || '';
        },

        isSessionClosed() {
            return ['completed', 'cancelled'].includes(this.currentStatus);
        },

        sessionDurationSeconds() {
            const hours = Number(this.sessionState?.duration_hours || this.config.sessionDurationHours || 1);

            return hours * 60 * 60;
        },

        correctedNowMs() {
            return Date.now() + this.serverTimeOffsetMs;
        },

        syncServerTime(serverNowTs) {
            const timestamp = Number(serverNowTs || 0);

            if (!Number.isFinite(timestamp) || timestamp <= 0) {
                return;
            }

            this.serverTimeOffsetMs = (timestamp * 1000) - Date.now();
        },

        parseIsoMs(value) {
            if (!value) {
                return null;
            }

            const parsed = new Date(value).getTime();

            return Number.isNaN(parsed) ? null : parsed;
        },

        bothParticipantsJoined() {
            return Boolean(this.sessionState?.teacher_joined_at && this.sessionState?.student_joined_at);
        },

        startedAtMs() {
            const stateTimestamp = Number(this.sessionState?.started_at_ts || 0);
            const configTimestamp = Number(this.config.sessionStartedAt || 0);

            return (Number.isFinite(stateTimestamp) && stateTimestamp > 0 ? stateTimestamp * 1000 : null)
                || this.parseIsoMs(this.sessionState?.started_at)
                || (Number.isFinite(configTimestamp) && configTimestamp > 0 ? configTimestamp * 1000 : null);
        },

        countdownEndMs() {
            if (!this.bothParticipantsJoined()) {
                return null;
            }

            const stateTimestamp = Number(this.sessionState?.planned_end_at_ts || 0);
            const plannedEndAt = this.parseIsoMs(this.sessionState?.planned_end_at);

            if (Number.isFinite(stateTimestamp) && stateTimestamp > 0) {
                return stateTimestamp * 1000;
            }

            if (plannedEndAt) {
                return plannedEndAt;
            }

            const startedAtMs = this.startedAtMs();

            if (!startedAtMs) {
                return null;
            }

            return startedAtMs + (this.sessionDurationSeconds() * 1000);
        },

        calculateTimeRemaining() {
            const endMs = this.countdownEndMs();

            if (!endMs) {
                return null;
            }

            return Math.max(0, Math.floor((endMs - this.correctedNowMs()) / 1000));
        },

        startTimer() {
            this.timeRemaining = this.calculateTimeRemaining();

            this.timerInterval = window.setInterval(() => {
                this.timeRemaining = this.calculateTimeRemaining();

                if (this.timeRemaining === 0 && !this.zeroHandled) {
                    this.zeroHandled = true;
                    this.handleTimerExpiry();
                }
            }, 1000);
        },

        stopTimer() {
            if (!this.timerInterval) {
                return;
            }

            window.clearInterval(this.timerInterval);
            this.timerInterval = null;
        },

        startStatePolling() {
            this.pollState();

            this.stateInterval = window.setInterval(() => {
                this.pollState();
            }, 1200);
        },

        stopStatePolling() {
            if (!this.stateInterval) {
                return;
            }

            window.clearInterval(this.stateInterval);
            this.stateInterval = null;
        },

        async pollState() {
            if (!this.config.stateUrl || this.redirecting) {
                return;
            }

            try {
                const response = await axios.get(this.config.stateUrl);
                await this.syncState(response.data);
            } catch (error) {
                console.warn('State sync failed:', error?.message || error);
            }
        },

        async syncState(payload) {
            if (!payload?.session) {
                return;
            }

            this.syncServerTime(payload.server_now_ts);
            this.sessionState = payload.session;
            if (Array.isArray(payload.messages)) {
                this.syncMessages(payload.messages);
            }
            this.timeRemaining = this.calculateTimeRemaining();

            if (this.timeRemaining > 0 || this.timeRemaining === null) {
                this.zeroHandled = false;
            }

            if (this.isSessionClosed()) {
                await this.handleSessionClosed();
                return;
            }

            this.syncRecordingAudioInputs();
            await this.maybeStartRecording();

            if (this.config.autojoin && this.canJoinNow && !this.joined && !this.joining) {
                this.startCall();
            }
        },

        async handleTimerExpiry() {
            await this.pollState();

            if (this.isSessionClosed()) {
                return;
            }

            if (this.currentStatus === 'in_progress') {
                await this.endSession({ silent: true });
            }
        },

        handleBeforeUnload() {
            if (!this.joined || this.isSessionClosed() || this.ending || !this.config.endUrl) {
                return;
            }

            const payload = new URLSearchParams();
            const csrf = this.csrfToken();

            if (csrf) {
                payload.append('_token', csrf);
            }

            payload.append('confirm_end', '1');
            navigator.sendBeacon(this.config.endUrl, payload);
        },

        initRoomChannel() {
            if (!window.Echo || !this.config.realtimeChannel) {
                return;
            }

            window.Echo.channel(this.config.realtimeChannel).listen('.live-session.event', async event => {
                if (event?.session) {
                    this.sessionState = {
                        ...(this.sessionState || {}),
                        ...event.session,
                    };
                    this.timeRemaining = this.calculateTimeRemaining();
                    this.syncRecordingAudioInputs();
                    await this.maybeStartRecording();
                }

                if (event?.type === 'message' && event.payload) {
                    this.mergeMessage(event.payload);
                    return;
                }

                if (ROOM_EVENT_TYPES_REQUIRING_SYNC.includes(event?.type)) {
                    await this.pollState();
                }
            });
        },

        getContainer(target) {
            return typeof target === 'string' ? document.getElementById(target) : target;
        },

        clearVideoContainer(target) {
            const container = this.getContainer(target);

            if (!container) {
                return;
            }

            const video = container.querySelector('video');

            if (video) {
                video.pause();
                video.srcObject = null;
            }

            container.innerHTML = '';
        },

        mountVideoTrack(track, target, { muted = false, fit = 'cover' } = {}) {
            const container = this.getContainer(target);

            if (!container || !track) {
                return false;
            }

            const mediaStreamTrack = typeof track.getMediaStreamTrack === 'function'
                ? track.getMediaStreamTrack()
                : null;

            if (!mediaStreamTrack) {
                track.play(container, { fit });
                return true;
            }

            const stream = new MediaStream([mediaStreamTrack]);
            const video = document.createElement('video');

            video.autoplay = true;
            video.playsInline = true;
            video.muted = muted;
            video.srcObject = stream;
            video.style.display = 'block';
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = fit;
            video.style.objectPosition = 'center';
            video.style.backgroundColor = '#000';

            this.clearVideoContainer(container);
            container.appendChild(video);

            const playPromise = video.play();

            if (playPromise?.catch) {
                playPromise.catch(error => {
                    console.warn('HTML video play failed:', error?.message || error);
                });
            }

            return true;
        },

        refreshLocalPreview() {
            const videoTrack = this.localTracks[1];

            if (!videoTrack || !this.cameraOn) {
                return;
            }

            this.mountVideoTrack(videoTrack, 'local-player', {
                muted: true,
                fit: this.hasRemote ? 'cover' : 'cover',
            });

            void this.maybeStartRecording();
        },

        getLocalVideoElement() {
            return document.querySelector('#local-player video');
        },

        getRemoteVideoElement() {
            return document.querySelector('#remote-player video');
        },

        getCallContainerElement() {
            return this.$el instanceof HTMLElement
                ? this.$el
                : document.querySelector('.call-container');
        },

        syncRecordingCanvasSize() {
            if (!this.recordingCanvas) {
                return;
            }

            const container = this.getCallContainerElement();
            const rect = container?.getBoundingClientRect?.();
            let targetWidth = 1280;
            let targetHeight = 720;

            if (rect?.width && rect?.height) {
                const maxLongSide = 1280;
                const scale = maxLongSide / Math.max(rect.width, rect.height);

                targetWidth = Math.max(2, Math.round((rect.width * scale) / 2) * 2);
                targetHeight = Math.max(2, Math.round((rect.height * scale) / 2) * 2);
            }

            if (
                this.recordingCanvas.width !== targetWidth
                || this.recordingCanvas.height !== targetHeight
            ) {
                this.recordingCanvas.width = targetWidth;
                this.recordingCanvas.height = targetHeight;
            }
        },

        ensureRecordingCanvas() {
            if (!this.recordingCanvas) {
                this.recordingCanvas = document.createElement('canvas');
                this.recordingCanvasCtx = this.recordingCanvas.getContext('2d');
            }

            this.syncRecordingCanvasSize();

            return this.recordingCanvas;
        },

        drawVideoCover(source, x, y, width, height) {
            if (!this.recordingCanvasCtx || !source?.videoWidth || !source?.videoHeight) {
                return;
            }

            const scale = Math.min(width / source.videoWidth, height / source.videoHeight);
            const drawWidth = source.videoWidth * scale;
            const drawHeight = source.videoHeight * scale;
            const offsetX = x + ((width - drawWidth) / 2);
            const offsetY = y + ((height - drawHeight) / 2);

            this.recordingCanvasCtx.drawImage(source, offsetX, offsetY, drawWidth, drawHeight);
        },

        drawRecordingFrame() {
            const canvas = this.ensureRecordingCanvas();
            const ctx = this.recordingCanvasCtx;

            if (!canvas || !ctx) {
                return;
            }

            const width = canvas.width;
            const height = canvas.height;
            const remoteVideo = this.getRemoteVideoElement();
            const localVideo = this.getLocalVideoElement();

            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, width, height);

            if (remoteVideo && remoteVideo.readyState >= 2) {
                this.drawVideoCover(remoteVideo, 0, 0, width, height);
            } else {
                ctx.fillStyle = '#111827';
                ctx.fillRect(0, 0, width, height);
                ctx.fillStyle = '#f9fafb';
                ctx.font = '600 34px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('Waiting for remote video', width / 2, height / 2);
            }

            if (localVideo && localVideo.readyState >= 2) {
                const insetWidth = Math.round(width * 0.24);
                const insetHeight = Math.round(insetWidth * 0.5625);
                const insetX = 28;
                const insetY = height - insetHeight - 28;

                ctx.save();
                ctx.fillStyle = '#000';
                ctx.fillRect(insetX - 4, insetY - 4, insetWidth + 8, insetHeight + 8);
                this.drawVideoCover(localVideo, insetX, insetY, insetWidth, insetHeight);
                ctx.restore();
            }

            this.recordingRenderFrameId = window.requestAnimationFrame(() => this.drawRecordingFrame());
        },

        stopRecordingFrameLoop() {
            if (!this.recordingRenderFrameId) {
                return;
            }

            window.cancelAnimationFrame(this.recordingRenderFrameId);
            this.recordingRenderFrameId = null;
        },

        disconnectRecordingAudioSource(key) {
            const source = this.recordingAudioSources[key];

            if (!source) {
                return;
            }

            try {
                source.node.disconnect();
            } catch (error) {
                console.warn('Audio source disconnect failed:', error?.message || error);
            }

            delete this.recordingAudioSources[key];
        },

        syncRecordingAudioSource(key, track) {
            if (!this.recordingAudioContext || !this.recordingAudioDestination || !track) {
                if (!track) {
                    this.disconnectRecordingAudioSource(key);
                }
                return;
            }

            const mediaStreamTrack = typeof track.getMediaStreamTrack === 'function'
                ? track.getMediaStreamTrack()
                : null;

            if (!mediaStreamTrack) {
                return;
            }

            const existing = this.recordingAudioSources[key];

            if (existing?.id === mediaStreamTrack.id) {
                return;
            }

            this.disconnectRecordingAudioSource(key);

            const stream = new MediaStream([mediaStreamTrack]);
            const node = this.recordingAudioContext.createMediaStreamSource(stream);

            node.connect(this.recordingAudioDestination);

            this.recordingAudioSources[key] = {
                id: mediaStreamTrack.id,
                node,
            };
        },

        syncRecordingAudioInputs() {
            if (!this.recordingAudioContext || !this.recordingAudioDestination) {
                return;
            }

            this.syncRecordingAudioSource('local', this.localTracks[0] || null);
            this.syncRecordingAudioSource('remote', this.remoteAudioTrack || null);
        },

        async buildRecordingStream() {
            const canvas = this.ensureRecordingCanvas();
            const AudioContextCtor = window.AudioContext || window.webkitAudioContext;

            if (!this.recordingAudioContext) {
                this.recordingAudioContext = new AudioContextCtor();
                this.recordingAudioDestination = this.recordingAudioContext.createMediaStreamDestination();
            }

            if (this.recordingAudioContext.state === 'suspended') {
                await this.recordingAudioContext.resume().catch(() => { });
            }

            this.syncRecordingAudioInputs();
            this.stopRecordingFrameLoop();
            this.drawRecordingFrame();

            this.recordingCanvasStream = canvas.captureStream(20);

            const tracks = [];
            const canvasTrack = this.recordingCanvasStream.getVideoTracks()[0];
            const audioTrack = this.recordingAudioDestination.stream.getAudioTracks()[0];

            if (canvasTrack) {
                tracks.push(canvasTrack);
            }

            if (audioTrack) {
                tracks.push(audioTrack);
            }

            return new MediaStream(tracks);
        },

        cleanupRecordingResources() {
            this.stopRecordingFrameLoop();

            Object.keys(this.recordingAudioSources).forEach(key => {
                this.disconnectRecordingAudioSource(key);
            });

            if (this.recordingCanvasStream) {
                this.recordingCanvasStream.getTracks().forEach(track => track.stop());
                this.recordingCanvasStream = null;
            }

            if (this.recordingAudioContext) {
                this.recordingAudioContext.close().catch(() => { });
                this.recordingAudioContext = null;
                this.recordingAudioDestination = null;
            }

            this.recordingCanvas = null;
            this.recordingCanvasCtx = null;
        },

        async maybeStartRecording() {
            if (
                !this.supportsRecording()
                || this.mediaRecorder
                || this.recordingStopPromise
                || this.recordingSaved
                || this.isSessionClosed()
                || !this.joined
                || !this.bothParticipantsJoined()
                || !this.localTracks[1]
            ) {
                return;
            }

            try {
                this.recordingStatus = 'starting';

                const stream = await this.buildRecordingStream();
                const mimeType = this.recordingMimeType();

                this.mediaRecorder = mimeType
                    ? new MediaRecorder(stream, { mimeType })
                    : new MediaRecorder(stream);

                this.recordingChunks = [];

                this.mediaRecorder.ondataavailable = event => {
                    if (event.data?.size) {
                        this.recordingChunks.push(event.data);
                    }
                };

                this.mediaRecorder.onerror = () => {
                    this.recordingStatus = 'error';
                };

                this.mediaRecorder.start(1000);
                this.recordingStatus = 'recording';
            } catch (error) {
                console.warn('Automatic recording could not start:', error?.message || error);
                this.mediaRecorder = null;
                this.recordingStatus = 'error';
                this.cleanupRecordingResources();
            }
        },

        async stopRecordingAndUpload() {
            if (!this.mediaRecorder) {
                this.cleanupRecordingResources();
                return;
            }

            if (this.recordingStopPromise) {
                await this.recordingStopPromise;
                return;
            }

            const recorder = this.mediaRecorder;
            const mimeType = recorder.mimeType || this.recordingMimeType() || 'video/webm';

            this.recordingStatus = 'uploading';

            this.recordingStopPromise = new Promise(resolve => {
                let finalized = false;

                const finalize = async () => {
                    if (finalized) {
                        return;
                    }

                    finalized = true;

                    const blob = this.recordingChunks.length
                        ? new Blob(this.recordingChunks, { type: mimeType })
                        : null;

                    this.mediaRecorder = null;
                    this.recordingChunks = [];
                    this.cleanupRecordingResources();

                    if (!blob?.size || !this.config.recordingUrl) {
                        this.recordingStatus = this.recordingSaved ? 'saved' : 'idle';
                        this.recordingStopPromise = null;
                        resolve();
                        return;
                    }

                    const extension = mimeType.includes('mp4') ? 'mp4' : 'webm';
                    const formData = new FormData();

                    formData.append('recording', blob, `session-${this.config.sessionId}.${extension}`);

                    try {
                        const response = await axios.post(this.config.recordingUrl, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            },
                        });

                        this.recordingSaved = true;
                        this.recordingStatus = 'saved';

                        if (response.data?.recording_url) {
                            this.sessionState = {
                                ...(this.sessionState || {}),
                                recording_url: response.data.recording_url,
                            };
                        }

                        await this.pollState();
                    } catch (error) {
                        console.warn('Recording upload failed:', error?.message || error);
                        this.recordingStatus = 'error';
                    } finally {
                        this.recordingStopPromise = null;
                        resolve();
                    }
                };

                recorder.onstop = () => {
                    void finalize();
                };

                if (recorder.state === 'inactive') {
                    void finalize();
                    return;
                }

                try {
                    recorder.stop();
                } catch (error) {
                    console.warn('Recording stop failed:', error?.message || error);
                    void finalize();
                }
            });

            await this.recordingStopPromise;
        },

        async startCall() {
            if (this.joined || this.joining || this.isSessionClosed() || !this.canJoinNow) {
                return;
            }

            this.joining = true;

            try {
                if (!this.client) {
                    this.client = AgoraRTC.createClient({
                        mode: 'rtc',
                        codec: 'vp8',
                    });

                    this.client.on('user-published', (user, mediaType) => this.handleUserPublished(user, mediaType));
                    this.client.on('user-unpublished', (user, mediaType) => this.handleUserUnpublished(user, mediaType));
                    this.client.on('user-left', user => this.handleUserLeft(user));
                }

                const tokenEndpoint = this.config.agoraTokenUrl || '/agora/token';
                const tokenUrl = `${tokenEndpoint}${tokenEndpoint.includes('?') ? '&' : '?'}channel=${encodeURIComponent(this.channel)}`;
                const response = await axios.get(tokenUrl);

                this.token = response.data.token || response.data.accessToken || null;
                const appId = response.data.appId || response.data.app_id || this.config.appId || null;

                if (!appId || !this.token) {
                    throw new Error('Agora appId or token missing.');
                }

                await this.client.join(appId, this.channel, this.token, null);

                const [audioTrack, videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
                this.localTracks = [audioTrack, videoTrack];

                this.mountVideoTrack(videoTrack, 'local-player', {
                    muted: true,
                    fit: 'cover',
                });

                await this.client.publish(this.localTracks);

                if (this.config.joinUrl) {
                    await axios.post(this.config.joinUrl, {});
                }

                this.joined = true;
                await this.pollState();
                await this.maybeStartRecording();
            } catch (error) {
                console.error(error);
                await this.performLocalLeave();
                alert('Unable to join the call right now. Please try again.');
            } finally {
                this.joining = false;
            }
        },

        async handleUserPublished(user, mediaType) {
            const maxRetries = 10;
            let retries = 0;
            let remoteUser = null;

            while (retries < maxRetries) {
                remoteUser = this.client.remoteUsers.find(candidate => candidate.uid === user.uid);

                if (remoteUser) {
                    break;
                }

                await this.sleep(200);
                retries++;
            }

            if (!remoteUser) {
                console.warn('Remote user not ready:', user.uid);
                return;
            }

            try {
                await this.client.subscribe(remoteUser, mediaType);

                if (mediaType === 'audio') {
                    const audioTrack = remoteUser.audioTrack || user.audioTrack;

                    if (audioTrack) {
                        this.remoteAudioTrack = audioTrack;
                        audioTrack.play();
                        this.syncRecordingAudioInputs();
                        await this.maybeStartRecording();
                    }

                    return;
                }

                if (mediaType !== 'video') {
                    return;
                }

                let container = document.getElementById(`remote-${remoteUser.uid}`);

                if (!container) {
                    container = document.createElement('div');
                    container.id = `remote-${remoteUser.uid}`;
                    container.className = 'remote-inner';
                    document.getElementById('remote-player')?.appendChild(container);
                }

                retries = 0;

                while (!(remoteUser.videoTrack || user.videoTrack) && retries < maxRetries) {
                    await this.sleep(200);
                    retries++;
                }

                const videoTrack = remoteUser.videoTrack || user.videoTrack;

                if (!videoTrack) {
                    console.warn('Remote video track not ready:', remoteUser.uid);
                    return;
                }

                this.remoteVideoTrack = videoTrack;
                this.mountVideoTrack(videoTrack, container, { fit: 'cover' });
                this.hasRemote = true;
                await this.maybeStartRecording();
            } catch (error) {
                console.warn('Subscribe failed:', error?.message || error);
            }
        },

        handleUserUnpublished(user, mediaType) {
            if (mediaType === 'audio') {
                this.remoteAudioTrack = null;
                this.syncRecordingAudioInputs();
            }

            if (mediaType !== 'video') {
                return;
            }

            const container = document.getElementById(`remote-${user.uid}`);

            if (container) {
                this.clearVideoContainer(container);
                container.remove();
            }

            if (user.videoTrack === this.remoteVideoTrack) {
                this.remoteVideoTrack = null;
            }

            this.hasRemote = Boolean(document.querySelector('#remote-player .remote-inner'));
        },

        handleUserLeft(user) {
            const container = document.getElementById(`remote-${user.uid}`);

            if (container) {
                this.clearVideoContainer(container);
                container.remove();
            }

            this.remoteAudioTrack = null;
            this.syncRecordingAudioInputs();

            if (user.videoTrack === this.remoteVideoTrack) {
                this.remoteVideoTrack = null;
            }

            this.hasRemote = Boolean(document.querySelector('#remote-player .remote-inner'));
        },

        toggleMic() {
            if (!this.localTracks[0]) {
                return;
            }

            this.micOn = !this.micOn;
            this.localTracks[0].setEnabled(this.micOn);

            this.$nextTick(() => {
                this.refreshLocalPreview();
            });
        },

        async toggleCamera() {
            if (!this.localTracks[1]) {
                return;
            }

            this.cameraOn = !this.cameraOn;
            await this.localTracks[1].setEnabled(this.cameraOn);

            this.$nextTick(() => {
                this.refreshLocalPreview();
            });

            if (this.cameraOn) {
                this.refreshLocalPreview();
                return;
            }

            this.clearVideoContainer('local-player');
        },

        async performLocalLeave() {
            const remotePlayer = document.getElementById('remote-player');

            if (this.localTracks.length) {
                this.localTracks.forEach(track => track.close());
                this.localTracks = [];
            }

            if (this.client) {
                try {
                    await this.client.leave();
                } catch (error) {
                    console.warn('Agora leave failed:', error?.message || error);
                }
            }

            this.client = null;
            this.joined = false;
            this.hasRemote = false;
            this.micOn = true;
            this.cameraOn = true;
            this.remoteAudioTrack = null;
            this.remoteVideoTrack = null;
            this.clearVideoContainer('local-player');

            if (remotePlayer) {
                remotePlayer.innerHTML = '';
            }
        },

        async handleSessionClosed() {
            if (this.redirecting) {
                return;
            }

            this.redirecting = true;
            await this.stopRecordingAndUpload();
            await this.performLocalLeave();

            window.setTimeout(() => {
                window.location.href = this.config.redirectUrl;
            }, 900);
        },

        async endSession({ silent = false, reason = null } = {}) {
            if (this.ending || this.isSessionClosed() || !this.config.endUrl) {
                return;
            }

            this.ending = true;

            try {
                const response = await axios.post(this.config.endUrl, {
                    confirm_end: true,
                    cancellation_reason: reason || null,
                });

                if (response.data?.session) {
                    await this.syncState({ session: response.data.session });
                    return;
                }

                await this.stopRecordingAndUpload();
                await this.performLocalLeave();

                if (response.data?.redirect_url) {
                    window.location.href = response.data.redirect_url;
                    return;
                }

                window.location.href = this.config.redirectUrl;
            } catch (error) {
                if (!silent) {
                    alert('Unable to end the session right now. Please try again.');
                }

                await this.pollState();
            } finally {
                this.ending = false;
            }
        },

        async handleLeaveClick() {
            this.leaveReason = '';
            this.showLeaveDialog = true;
        },

        async confirmLeave() {
            this.showLeaveDialog = false;
            await this.endSession({
                reason: this.isEarlyLeave ? this.leaveReason.trim() : null,
            });
        },

        toggleChat() {
            this.chatOpen = !this.chatOpen;

            if (this.chatOpen) {
                this.chatUnread = 0;
                this.scrollChatToBottom();
            }
        },

        async sendMessage() {
            const text = this.chatMessageText.trim();

            if (!text || this.chatSending) {
                return;
            }

            this.chatSending = true;
            this.chatMessageText = '';

            try {
                const response = await axios.post(this.config.messageUrl, {
                    message: text,
                });

                if (response.data?.message) {
                    this.mergeMessage(response.data.message);
                }

                await this.pollState();
            } finally {
                this.chatSending = false;
            }
        },
    },
};
</script>

<style scoped>
.call-container {
    position: relative;
    width: 100vw;
    height: 100dvh;
    min-height: 100vh;
    background: #000;
    overflow: hidden;
}

.remote-video,
.remote-inner {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #000;
}

.remote-video :deep(.agora_video_player),
.remote-inner :deep(.agora_video_player),
.local-video :deep(.agora_video_player) {
    position: absolute !important;
    inset: 0;
}

.remote-video :deep(video),
.remote-video :deep(canvas),
.remote-video :deep(.agora_video_player video),
.remote-inner :deep(video),
.remote-inner :deep(canvas),
.remote-inner :deep(.agora_video_player video) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    background: #000;
}

.local-video :deep(video),
.local-video :deep(canvas),
.local-video :deep(.agora_video_player video) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
    background: #000;
}

.local-video {
    position: absolute;
    left: 20px;
    bottom: 92px;
    z-index: 3;
    width: min(22vw, 240px);
    min-width: 140px;
    aspect-ratio: 16 / 9;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.92);
    border-radius: 18px;
    background: #000;
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.32);
    transition: width .2s, height .2s, inset .2s;

}

.call-container.solo-mode .local-video {
    inset: 0;
    width: 100%;
    height: 100%;
    min-width: 0;
    aspect-ratio: auto;
    border: 0;
    border-radius: 0;
    box-shadow: none;
}

.call-container:not(.solo-mode) .local-video {
    top: auto;
    right: auto;
}

.call-container.solo-mode .local-video :deep(video),
.call-container.solo-mode .local-video :deep(canvas),
.call-container.solo-mode .local-video :deep(.agora_video_player video) {
    object-fit: contain !important;
}

.session-timer {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 4;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(15, 15, 15, 0.72);
    color: #fff;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.timer-display {
    display: inline-block;
    min-width: 88px;
    text-align: center;
}

.recording-indicator {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 4;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(15, 15, 15, 0.72);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
}

.recording-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: #9ca3af;
    flex: 0 0 auto;
}

.recording-indicator.is-recording .recording-dot {
    background: #ef4444;
    box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.18);
}

.recording-indicator.is-uploading .recording-dot,
.recording-indicator.is-starting .recording-dot {
    background: #f59e0b;
}

.recording-indicator.is-saved .recording-dot {
    background: #22c55e;
}

.recording-indicator.is-error .recording-dot {
    background: #dc2626;
}

.recording-indicator.is-unsupported .recording-dot {
    background: #6b7280;
}

.controls {
    position: absolute;
    bottom: calc(18px + env(safe-area-inset-bottom, 0px));
    left: 50%;
    z-index: 4;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    transform: translateX(-50%);
}

.controls button {
    min-width: 78px;
    height: 48px;
    padding: 0 16px;
    border: 0;
    border-radius: 999px;
    background: rgba(43, 43, 43, 0.92);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.controls button.off {
    background: #b91c1c;
}

.controls .leave {
    background: #dc2626;
}

.join-btn {
    position: absolute;
    top: 20px;
    left: 50%;
    z-index: 4;
    padding: 10px 18px;
    border: 0;
    border-radius: 999px;
    background: #2563eb;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transform: translateX(-50%);
}

.join-btn:disabled,
.chat-input button:disabled {
    opacity: 0.6;
    cursor: wait;
}

.chat-panel {
    position: absolute;
    top: 0;
    right: 0;
    width: min(420px, 100vw);
    height: 100%;
    background: #fff;
    transform: translateX(100%);
    transition: transform 0.25s ease;
    z-index: 10;
    display: flex;
    flex-direction: column;
    box-shadow: -18px 0 44px rgba(0, 0, 0, 0.18);
}

.chat-panel.open {
    transform: translateX(0);
}

.chat-header {
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #eee;
    font-weight: 700;
}

.chat-header button {
    border: 0;
    background: transparent;
    font-size: 18px;
    line-height: 1;
}

.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.msg {
    display: flex;
}

.msg.mine {
    justify-content: flex-end;
}

.bubble {
    max-width: min(82%, 320px);
    padding: 10px 12px;
    border-radius: 14px;
    background: #e5e5e5;
    color: #111827;
}

.msg.mine .bubble {
    background: #2563eb;
    color: #fff;
}

.name {
    font-size: 11px;
    opacity: 0.75;
    margin-bottom: 4px;
}

.chat-input {
    display: flex;
    gap: 8px;
    padding: 12px;
    border-top: 1px solid #eee;
}

.chat-input input {
    flex: 1;
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
}

.chat-input button {
    padding: 0 16px;
    border: 0;
    border-radius: 12px;
    background: #2563eb;
    color: #fff;
    font-weight: 600;
}

.badge {
    background: #dc2626;
    color: #fff;
    border-radius: 999px;
    padding: 2px 6px;
    font-size: 11px;
    margin-left: 6px;
}

.leave-dialog-backdrop {
    position: absolute;
    inset: 0;
    z-index: 12;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.55);
}

.leave-dialog {
    width: min(460px, 100%);
    padding: 22px;
    border-radius: 22px;
    background: #fff;
    color: #111827;
    box-shadow: 0 24px 50px rgba(0, 0, 0, 0.28);
}

.leave-dialog h3 {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 700;
}

.leave-dialog p {
    margin: 0 0 14px;
    color: #4b5563;
    line-height: 1.7;
}

.leave-dialog textarea {
    width: 100%;
    min-height: 110px;
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    resize: vertical;
}

.leave-dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px;
}

.leave-dialog-actions button {
    border: 0;
    border-radius: 999px;
    min-height: 44px;
    padding: 0 18px;
    font-weight: 700;
}

.leave-dialog-actions .ghost {
    background: #e5e7eb;
    color: #111827;
}

.leave-dialog-actions .leave {
    background: #dc2626;
    color: #fff;
}

@media (max-width: 768px) {
    .local-video {
        left: 12px;
        bottom: 84px;
        width: min(34vw, 148px);
        min-width: 96px;
        border-radius: 14px;
    }

    .controls {
        width: calc(100% - 24px);
        gap: 8px;
    }

    .controls button {
        min-width: 70px;
        height: 44px;
        padding: 0 12px;
        font-size: 13px;
    }

    .session-timer {
        top: 12px;
        right: 12px;
        padding: 8px 12px;
    }

    .recording-indicator {
        top: 12px;
        left: 12px;
        padding: 8px 12px;
        font-size: 12px;
    }

    .join-btn {
        top: auto;
        bottom: 78px;
    }

    .chat-panel {
        top: auto;
        right: 12px;
        bottom: 112px;
        width: min(88vw, 360px);
        height: min(52dvh, 420px);
        border-radius: 24px;
        transform: translateX(calc(100% + 16px));
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
    }
}
</style>
