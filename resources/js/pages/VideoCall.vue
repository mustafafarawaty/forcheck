<template>
    <div class="call-container">
        <div id="remote-player" class="remote-video"></div>

        <div v-if="!hasRemote" class="overlay">
            <p>جاري انتظار الطرف الآخر...</p>
        </div>

        <div id="local-player" class="local-video"></div>

        <div class="controls">
            <button @click="toggleMic" :class="{ off: !micOn }">🎤</button>
            <button @click="toggleCamera" :class="{ off: !cameraOn }">📷</button>
            <button class="leave" @click="leaveCall">❌</button>
        </div>

        <button v-if="!joined" class="join-btn" @click="startCall">انضم الآن</button>
    </div>
</template>

<script>
import AgoraRTC from 'agora-rtc-sdk-ng';
import axios from 'axios';

export default {
    props: {
        config: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            client: null,
            localTracks: [],
            appId: null,
            token: null,
            channel: '',
            joinedUsers: new Set(),
            joined: false,
            hasRemote: false,
            micOn: true,
            cameraOn: true,
        };
    },

    created() {
        this.channel = this.config.roomChannel || `session-${this.config.sessionId}`;
    },

    methods: {
        sleep(ms) {
            return new Promise((resolve) => setTimeout(resolve, ms));
        },

        getContainer(target) {
            return typeof target === 'string' ? document.getElementById(target) : target;
        },

        clearVideoContainer(target) {
            const container = this.getContainer(target);

            if (!container) return;

            const video = container.querySelector('video');
            if (video) {
                video.pause();
                video.srcObject = null;
            }

            container.innerHTML = '';
        },

        mountVideoTrack(track, target, { muted = false, fit = 'contain' } = {}) {
            const container = this.getContainer(target);

            if (!container || !track) return false;

            const mediaStreamTrack = typeof track.getMediaStreamTrack === 'function' ? track.getMediaStreamTrack() : null;

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
                playPromise.catch((err) => {
                    console.warn('HTML video play failed:', err?.message || err);
                });
            }

            return true;
        },

        refreshLocalPreview() {
            const videoTrack = this.localTracks[1];

            if (!videoTrack || !this.cameraOn) return;

            this.mountVideoTrack(videoTrack, 'local-player', {
                muted: true,
                fit: 'contain',
            });
        },

        async startCall() {
            try {
                this.client = AgoraRTC.createClient({
                    mode: 'rtc',
                    codec: 'vp8',
                });

                this.client.on('user-published', this.handleUserPublished);
                this.client.on('user-unpublished', this.handleUserUnpublished);
                this.client.on('user-left', this.handleUserLeft);

                const tokenEndpoint = this.config.agoraTokenUrl || '/agora/token';
                const url = `${tokenEndpoint}${tokenEndpoint.includes('?') ? '&' : '?'}channel=${encodeURIComponent(this.channel)}`;
                const res = await axios.get(url);

                this.token = res.data.token || res.data.accessToken || null;
                this.appId = res.data.appId || res.data.app_id || null;

                await this.client.join(this.appId, this.channel, this.token, null);

                const [audioTrack, videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();

                this.localTracks = [audioTrack, videoTrack];

                this.mountVideoTrack(videoTrack, 'local-player', {
                    muted: true,
                    fit: 'contain',
                });

                await this.client.publish(this.localTracks);

                this.joined = true;
            } catch (err) {
                console.error(err);
                alert('تعذر الانضمام للمكالمة الآن. تأكد من الاتصال وحاول مجدداً.');
            }
        },

        async handleUserPublished(user, mediaType) {
            const maxRetries = 10;
            let retries = 0;
            let realUser = null;

            while (retries < maxRetries) {
                realUser = this.client.remoteUsers.find((u) => u.uid === user.uid);

                if (realUser) break;
                await this.sleep(200);
                retries++;
            }

            if (!realUser) {
                console.warn('User not ready, skipping:', user.uid);
                return;
            }

            try {
                await this.client.subscribe(realUser, mediaType);

                if (mediaType === 'audio') {
                    const audioTrack = realUser.audioTrack || user.audioTrack;
                    if (audioTrack) {
                        audioTrack.play();
                    }
                    return;
                }

                if (mediaType !== 'video') return;

                let container = document.getElementById(`remote-${realUser.uid}`);

                if (!container) {
                    container = document.createElement('div');
                    container.id = `remote-${realUser.uid}`;
                    container.className = 'remote-inner';
                    document.getElementById('remote-player').appendChild(container);
                }

                retries = 0;
                while (!(realUser.videoTrack || user.videoTrack) && retries < maxRetries) {
                    await this.sleep(200);
                    retries++;
                }

                const videoTrack = realUser.videoTrack || user.videoTrack;
                if (!videoTrack) {
                    console.warn('Video track not ready');
                    return;
                }

                this.mountVideoTrack(videoTrack, container, { fit: 'contain' });
                this.hasRemote = true;
            } catch (err) {
                console.warn('SUBSCRIBE FAILED:', err?.message || err);
            }
        },

        handleUserUnpublished(user, mediaType) {
            if (mediaType !== 'video') return;

            const el = document.getElementById(`remote-${user.uid}`);
            if (el) {
                this.clearVideoContainer(el);
                el.remove();
            }

            this.hasRemote = this.client?.remoteUsers?.some((remoteUser) => remoteUser.hasVideo) || false;
        },

        handleUserLeft(user) {
            const el = document.getElementById(`remote-${user.uid}`);
            if (el) {
                this.clearVideoContainer(el);
                el.remove();
            }

            this.hasRemote = this.client?.remoteUsers?.some((remoteUser) => remoteUser.uid !== user.uid && remoteUser.hasVideo) || false;
        },

        toggleMic() {
            if (!this.localTracks[0]) return;
            this.micOn = !this.micOn;
            this.localTracks[0].setEnabled(this.micOn);
        },

        async toggleCamera() {
            if (!this.localTracks[1]) return;
            this.cameraOn = !this.cameraOn;
            await this.localTracks[1].setEnabled(this.cameraOn);

            if (this.cameraOn) {
                this.refreshLocalPreview();
                return;
            }

            this.clearVideoContainer('local-player');
        },

        async leaveCall() {
            if (this.localTracks.length) {
                this.localTracks.forEach((track) => track.close());
            }

            if (this.client) {
                await this.client.leave();
            }

            this.joined = false;
            this.hasRemote = false;

            this.clearVideoContainer('local-player');
            const remotePlayer = document.getElementById('remote-player');
            if (remotePlayer) {
                remotePlayer.innerHTML = '';
            }

            if (this.config.redirectUrl) {
                window.location.href = this.config.redirectUrl;
            }
        },
    },
};
</script>

<style scoped>
.call-container {
    position: relative;
    width: 100%;
    height: 100dvh;
    min-height: 100vh;
    background: #0f0f0f;
    overflow: hidden;
}

.remote-video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.remote-inner {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.remote-video :deep(video),
.remote-video :deep(canvas),
.remote-video :deep(.agora_video_player),
.local-video :deep(video),
.local-video :deep(canvas),
.local-video :deep(.agora_video_player) {
    width: 100% !important;
    height: 100% !important;
    object-fit: contain;
}

.local-video {
    position: absolute;
    bottom: 100px;
    right: 20px;
    z-index: 2;
    width: min(32vw, 180px);
    aspect-ratio: 3 / 4;
    min-width: 110px;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #fff;
    background: #000;
}

.overlay {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    background: rgba(0, 0, 0, 0.5);
}

.controls {
    position: absolute;
    bottom: 20px;
    left: 50%;
    z-index: 4;
    transform: translateX(-50%);
    display: flex;
    gap: 15px;
}

.controls button {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    border: none;
    font-size: 20px;
    background: #2b2b2b;
    color: white;
    cursor: pointer;
}

.controls button.off {
    background: #b91c1c;
}

.controls .leave {
    background: red;
}

.join-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 4;
    padding: 10px 15px;
    background: #2563eb;
    color: white;
    border: none;
    cursor: pointer;
}

@media (max-width: 768px) {
    .local-video {
        right: 12px;
        bottom: 92px;
        width: min(34vw, 140px);
    }
}
</style>
