<x-filament-panels::page>
    <div
        x-data="recordingController()"
        x-init="initController()"
        class="flex flex-col gap-6"
    >
        <!-- Top bar: Device Selection & Status -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-white/10">
            <div class="w-full sm:w-1/2">
                <label for="videoSource" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Camera</label>
                <select
                    id="videoSource"
                    x-model="selectedDeviceId"
                    @change="startCamera()"
                    class="block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-gray-800 text-gray-900 dark:text-white focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    :disabled="isRecording"
                >
                    <template x-for="device in videoDevices" :key="device.deviceId">
                        <option :value="device.deviceId" x-text="device.label || 'Camera ' + ($index + 1)"></option>
                    </template>
                </select>
            </div>

            <div class="flex items-center justify-end gap-3">
                <div x-show="isRecording" class="flex items-center gap-2 text-danger-600 dark:text-danger-400 font-medium animate-pulse" style="display: none;">
                    <div class="w-3 h-3 rounded-full bg-danger-600 dark:bg-danger-400"></div>
                    <span x-text="formatTime(elapsedSeconds)">00:00</span>
                </div>

                <div x-show="!isRecording && recordedBlob" class="text-success-600 dark:text-success-400 font-medium" style="display: none;">
                    Recording ready (<span x-text="formatTime(elapsedSeconds)"></span>)
                </div>
            </div>
        </div>

        <!-- Video Preview Area -->
        <div class="relative flex justify-center bg-black rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-white/10 min-h-[400px]">
            <video
                x-ref="preview"
                autoplay
                muted
                playsinline
                class="w-full max-w-4xl max-h-[60vh] object-contain"
                x-show="!recordedBlobURL"
            ></video>

            <video
                x-ref="playback"
                controls
                playsinline
                class="w-full max-w-4xl max-h-[60vh] object-contain"
                x-show="recordedBlobURL"
                :src="recordedBlobURL"
                style="display: none;"
            ></video>

            <!-- Error State Overlay -->
            <div x-show="errorMessage" class="absolute inset-0 flex items-center justify-center bg-gray-900/80 text-white p-6 text-center" style="display: none;">
                <div>
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto text-danger-500 mb-4" />
                    <h3 class="text-lg font-bold mb-2">Camera Access Error</h3>
                    <p x-text="errorMessage" class="text-gray-300"></p>
                    <p class="text-sm text-gray-400 mt-4">Make sure you are accessing this site via localhost or HTTPS, and have granted camera permissions.</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center gap-4">
            <x-filament::button
                x-show="!isRecording && !recordedBlob"
                @click="startRecording()"
                color="danger"
                icon="heroicon-o-video-camera"
                size="lg"
            >
                Start Recording
            </x-filament::button>

            <x-filament::button
                x-show="isRecording"
                @click="stopRecording()"
                color="gray"
                icon="heroicon-o-stop-circle"
                size="lg"
                style="display: none;"
            >
                Stop Recording
            </x-filament::button>

            <div x-show="!isRecording && recordedBlob" class="flex gap-4" style="display: none;">
                <x-filament::button
                    @click="discardRecording()"
                    color="gray"
                    icon="heroicon-o-trash"
                    size="lg"
                    variant="outlined"
                >
                    Discard
                </x-filament::button>

                <x-filament::button
                    @click="saveRecording()"
                    color="primary"
                    icon="heroicon-o-arrow-up-tray"
                    size="lg"
                    x-bind:disabled="isUploading"
                >
                    <span x-show="!isUploading">Save Recording</span>
                    <span x-show="isUploading">Uploading...</span>
                </x-filament::button>
            </div>
        </div>
    </div>

    <!-- Inject CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        function recordingController() {
            return {
                videoDevices: [],
                selectedDeviceId: null,
                stream: null,
                mediaRecorder: null,
                recordedChunks: [],
                recordedBlob: null,
                recordedBlobURL: null,

                isRecording: false,
                isUploading: false,
                elapsedSeconds: 0,
                timerInterval: null,
                errorMessage: null,
                mimeType: '',

                async initController() {
                    try {
                        // Request initial permissions to enumerate devices
                        await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                        await this.getDevices();

                        if (this.videoDevices.length > 0) {
                            this.selectedDeviceId = this.videoDevices[0].deviceId;
                            await this.startCamera();
                        } else {
                            this.errorMessage = "No camera devices found.";
                        }
                    } catch (err) {
                        console.error("Initialization error:", err);
                        this.errorMessage = err.message || "Failed to access camera.";
                    }
                },

                async getDevices() {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    this.videoDevices = devices.filter(device => device.kind === 'videoinput');
                },

                async startCamera() {
                    this.stopCamera();
                    this.errorMessage = null;

                    try {
                        const constraints = {
                            audio: true,
                            video: this.selectedDeviceId ? { deviceId: { exact: this.selectedDeviceId } } : true
                        };

                        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                        this.$refs.preview.srcObject = this.stream;
                    } catch (err) {
                        console.error("Start camera error:", err);
                        this.errorMessage = err.message || "Failed to start camera.";
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                    }
                },

                getBestMimeType() {
                    const types = [
                        'video/webm;codecs=vp9,opus',
                        'video/webm;codecs=vp8,opus',
                        'video/webm',
                        'video/mp4'
                    ];

                    for (let type of types) {
                        if (MediaRecorder.isTypeSupported(type)) {
                            return type;
                        }
                    }
                    return '';
                },

                startRecording() {
                    this.recordedChunks = [];
                    this.mimeType = this.getBestMimeType();

                    const options = this.mimeType ? { mimeType: this.mimeType } : {};

                    try {
                        this.mediaRecorder = new MediaRecorder(this.stream, options);

                        this.mediaRecorder.ondataavailable = (e) => {
                            if (e.data && e.data.size > 0) {
                                this.recordedChunks.push(e.data);
                            }
                        };

                        this.mediaRecorder.onstop = () => {
                            this.recordedBlob = new Blob(this.recordedChunks, { type: this.mimeType || 'video/webm' });
                            this.recordedBlobURL = URL.createObjectURL(this.recordedBlob);
                        };

                        this.mediaRecorder.start();
                        this.isRecording = true;
                        this.elapsedSeconds = 0;
                        this.timerInterval = setInterval(() => { this.elapsedSeconds++; }, 1000);
                    } catch (err) {
                        console.error("Recording error:", err);
                        this.errorMessage = "Failed to start recording: " + err.message;
                    }
                },

                stopRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                    }
                    this.isRecording = false;
                    clearInterval(this.timerInterval);
                },

                discardRecording() {
                    this.recordedChunks = [];
                    this.recordedBlob = null;
                    if (this.recordedBlobURL) {
                        URL.revokeObjectURL(this.recordedBlobURL);
                        this.recordedBlobURL = null;
                    }
                    this.elapsedSeconds = 0;
                },

                async saveRecording() {
                    if (!this.recordedBlob) return;

                    this.isUploading = true;

                    const formData = new FormData();
                    const ext = this.mimeType.includes('mp4') ? 'mp4' : 'webm';
                    const filename = `recording_${new Date().getTime()}.${ext}`;

                    formData.append('video', this.recordedBlob, filename);
                    formData.append('title', 'Recording ' + new Date().toLocaleString());
                    formData.append('duration_seconds', this.elapsedSeconds);
                    formData.append('recorded_at', new Date().toISOString());

                    try {
                        const response = await fetch('/api/recordings/upload', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Upload failed with status: ${response.status}`);
                        }

                        const result = await response.json();

                        // Redirect to the resource index or view
                        window.location.href = '/admin/recordings';
                    } catch (err) {
                        console.error("Save error:", err);
                        alert("Failed to upload recording: " + err.message);
                        this.isUploading = false;
                    }
                },

                formatTime(seconds) {
                    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                    const s = (seconds % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                }
            }
        }
    </script>
</x-filament-panels::page>
