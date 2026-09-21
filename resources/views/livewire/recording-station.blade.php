<div class="max-w-4xl mx-auto p-4" x-data="recordingStation()">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-4">PACKINGIN - Recording Station</h1>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Scan / Masukkan Nomor Resi</label>
                <input
                    type="text"
                    x-model="resi"
                    x-ref="resiInput"
                    @keyup.enter="handleResiEnter"
                    :disabled="isRecording || isUploading"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg p-2 border"
                    placeholder="Scan Barcode..."
                    autofocus
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Platform</label>
                <select
                    x-model="platform"
                    :disabled="isRecording || isUploading"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-lg p-2 border"
                >
                    <option value="shopee">Shopee</option>
                    <option value="tiktok">TikTok Shop</option>
                    <option value="tokopedia">Tokopedia</option>
                    <option value="lazada">Lazada</option>
                    <option value="blibli">Blibli</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>

        <div class="relative bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center">
            <video x-ref="videoElement" class="w-full h-full object-cover" autoplay muted playsinline></video>

            <div class="absolute top-4 left-4 bg-black/50 text-white px-3 py-1 rounded text-sm">
                <span x-text="resi ? resi : 'NO RESI'"></span>
                <span x-show="platform" x-text="' | ' + platform.toUpperCase()"></span>
            </div>

            <div class="absolute top-4 right-4 bg-black/50 text-white px-3 py-1 rounded flex items-center gap-2">
                <div class="w-3 h-3 rounded-full" :class="isRecording ? 'bg-red-500 animate-pulse' : 'bg-gray-400'"></div>
                <span x-text="formattedTimer"></span>
            </div>

            <div x-show="error" class="absolute inset-0 bg-black/80 flex items-center justify-center text-red-500 p-4 text-center font-bold" x-text="error" style="display: none;"></div>
            <div x-show="successMessage" class="absolute inset-0 bg-green-500/80 flex items-center justify-center text-white p-4 text-center font-bold text-xl" x-text="successMessage" style="display: none;"></div>
            <div x-show="isUploading" class="absolute inset-0 bg-black/80 flex items-center justify-center text-white p-4 text-center font-bold text-xl" style="display: none;">
                Uploading video, please wait...
            </div>
        </div>

        <div class="mt-6 flex justify-center gap-4">
            <button
                @click="startRecording()"
                x-show="!isRecording"
                :disabled="!isReadyToRecord || isUploading"
                class="px-8 py-3 bg-red-600 text-white font-bold rounded-lg shadow hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                START RECORDING
            </button>
            <button
                @click="stopRecording()"
                x-show="isRecording"
                class="px-8 py-3 bg-gray-800 text-white font-bold rounded-lg shadow hover:bg-gray-900"
                style="display: none;"
            >
                STOP RECORDING
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('recordingStation', () => ({
                resi: '',
                platform: 'shopee',
                isRecording: false,
                isUploading: false,
                stream: null,
                mediaRecorder: null,
                chunks: [],
                timer: 0,
                timerInterval: null,
                error: '',
                successMessage: '',
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),

                get isReadyToRecord() {
                    return this.resi.trim().length > 0 && this.stream !== null;
                },

                get formattedTimer() {
                    const m = Math.floor(this.timer / 60).toString().padStart(2, '0');
                    const s = (this.timer % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                },

                async init() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                        this.$refs.videoElement.srcObject = this.stream;
                    } catch (err) {
                        this.error = 'Camera access denied or not available. ' + err.message;
                    }

                    // Ensure focus on load
                    this.focusInput();
                },

                focusInput() {
                    setTimeout(() => {
                        if (this.$refs.resiInput) {
                            this.$refs.resiInput.focus();
                        }
                    }, 100);
                },

                handleResiEnter() {
                    if (!this.resi.trim()) return;

                    // Simple validation for safe characters (alphanumeric and dashes)
                    if (/[^a-zA-Z0-9\-]/.test(this.resi)) {
                        this.error = 'Resi contains invalid characters.';
                        setTimeout(() => this.error = '', 3000);
                        return;
                    }

                    if (this.isReadyToRecord && !this.isRecording) {
                        this.startRecording();
                    }
                },

                startRecording() {
                    if (!this.stream) return;

                    this.error = '';
                    this.successMessage = '';
                    this.chunks = [];

                    // Prefer webm
                    const mimeTypes = [
                        'video/webm;codecs=vp9',
                        'video/webm;codecs=vp8',
                        'video/webm',
                        'video/mp4'
                    ];

                    let selectedMimeType = '';
                    for (const type of mimeTypes) {
                        if (MediaRecorder.isTypeSupported(type)) {
                            selectedMimeType = type;
                            break;
                        }
                    }

                    try {
                        this.mediaRecorder = new MediaRecorder(this.stream, { mimeType: selectedMimeType });
                    } catch (e) {
                        this.mediaRecorder = new MediaRecorder(this.stream);
                    }

                    this.mediaRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) {
                            this.chunks.push(e.data);
                        }
                    };

                    this.mediaRecorder.onstop = () => {
                        this.uploadRecording();
                    };

                    this.mediaRecorder.start(1000); // chunk every 1s
                    this.isRecording = true;

                    this.timer = 0;
                    this.timerInterval = setInterval(() => {
                        this.timer++;
                    }, 1000);
                },

                stopRecording() {
                    if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                        this.mediaRecorder.stop();
                        this.isRecording = false;
                        clearInterval(this.timerInterval);
                    }
                },

                async uploadRecording() {
                    this.isUploading = true;

                    const blob = new Blob(this.chunks, { type: this.mediaRecorder.mimeType });
                    const formData = new FormData();
                    formData.append('video', blob, 'recording.webm');
                    formData.append('resi', this.resi);
                    formData.append('platform', this.platform);
                    formData.append('duration', this.timer);
                    formData.append('mime_type', this.mediaRecorder.mimeType);

                    try {
                        const response = await fetch('/api/recordings/upload', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const result = await response.json();

                        if (response.ok) {
                            this.successMessage = 'Recording uploaded successfully!';
                            this.resi = '';
                            setTimeout(() => {
                                this.successMessage = '';
                                this.focusInput();
                            }, 1500);
                        } else {
                            throw new Error(result.message || 'Upload failed');
                        }
                    } catch (err) {
                        this.error = 'Upload error: ' + err.message;
                        setTimeout(() => {
                            this.error = '';
                            this.focusInput();
                        }, 3000);
                    } finally {
                        this.isUploading = false;
                    }
                }
            }));
        });
    </script>
</div>