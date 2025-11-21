/**
 * Employee Management System
 * Smart HR Bot - Chat Interface JavaScript
 */

class ChatInterface {
    constructor() {
        this.apiUrl = window.BOT_API_URL || '/api/hrbot.php';
        this.messagesContainer = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('messageInput');
        this.sendBtn = document.getElementById('sendBtn');
        this.voiceRecordBtn = document.getElementById('voiceRecordBtn');
        this.recordingIndicator = document.getElementById('recordingIndicator');
        this.isRecording = false;
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.lottieAnimation = null;
        
        this.init();
    }

    init() {
        // Initialize Lottie Loader
        this.initLottieLoader();
        
        // Event Listeners
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Quick Reply Buttons
        document.querySelectorAll('.quick-reply').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const message = e.target.closest('.quick-reply').dataset.message;
                this.messageInput.value = message;
                this.sendMessage();
            });
        });
        
        // Voice Recording
        this.voiceRecordBtn.addEventListener('click', () => this.toggleVoiceRecording());
        
        // Focus input on load
        this.messageInput.focus();
    }

    /**
     * Initialize Lottie Loader
     */
    initLottieLoader() {
        // Create a simple loading animation using Lottie
        // You can replace this with your own Lottie JSON file
        const container = document.getElementById('lottieContainer');
        
        // Simple loading animation (you can use a custom Lottie JSON)
        this.lottieAnimation = lottie.loadAnimation({
            container: container,
            renderer: 'svg',
            loop: true,
            autoplay: false,
            path: 'https://assets5.lottiefiles.com/packages/lf20_jcikwtux.json' // Replace with your own
        });
    }

    /**
     * Send Message
     */
    async sendMessage() {
        const message = this.messageInput.value.trim();
        
        if (!message && !this.audioChunks.length) {
            return;
        }

        // Add user message
        if (message) {
            this.addMessage(message, 'user');
            this.messageInput.value = '';
        }

        // Show loading
        this.showLoading();

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: message
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Hide loading
            this.hideLoading();

            if (data.success) {
                this.addMessage(data.response, 'bot');
            } else {
                this.addMessage('عذراً، حدث خطأ: ' + (data.error || 'خطأ غير معروف'), 'bot', true);
            }
        } catch (error) {
            console.error('Chat Error:', error);
            this.hideLoading();
            this.addMessage('عذراً، حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.', 'bot', true);
        }
    }

    /**
     * Add Message to Chat
     */
    addMessage(text, type = 'bot', isError = false) {
        const messageDiv = document.createElement('div');
        const timestamp = new Date().toLocaleTimeString('ar', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        if (type === 'user') {
            messageDiv.className = 'chat chat-end animate__animated animate__fadeInUp';
            messageDiv.innerHTML = `
                <div class="chat-image avatar">
                    <div class="w-10 rounded-full bg-secondary text-secondary-content flex items-center justify-center">
                        <span class="material-icons">person</span>
                    </div>
                </div>
                <div class="chat-header">
                    أنت
                    <time class="text-xs opacity-50">${timestamp}</time>
                </div>
                <div class="chat-bubble chat-bubble-secondary">
                    ${this.formatMessage(text)}
                </div>
            `;
        } else {
            messageDiv.className = 'chat chat-start animate__animated animate__fadeInUp';
            const bubbleClass = isError ? 'chat-bubble-error' : 'chat-bubble-primary';
            
            messageDiv.innerHTML = `
                <div class="chat-image avatar">
                    <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                        <span class="material-icons">smart_toy</span>
                    </div>
                </div>
                <div class="chat-header">
                    مساعد HR
                    <time class="text-xs opacity-50">${timestamp}</time>
                </div>
                <div class="chat-bubble ${bubbleClass}">
                    ${this.formatMessage(text)}
                </div>
                <div class="chat-footer opacity-50">
                    <button class="btn btn-xs btn-ghost regenerate-btn" data-message="${this.escapeHtml(text)}">
                        <span class="material-icons text-xs">refresh</span>
                        أعد صياغة الرد
                    </button>
                </div>
            `;
            
            // Add regenerate button event listener
            const regenerateBtn = messageDiv.querySelector('.regenerate-btn');
            if (regenerateBtn) {
                regenerateBtn.addEventListener('click', () => {
                    this.regenerateResponse(text);
                });
            }
        }

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    /**
     * Add Audio Message
     */
    addAudioMessage(audioBlob) {
        const audioUrl = URL.createObjectURL(audioBlob);
        const messageDiv = document.createElement('div');
        const timestamp = new Date().toLocaleTimeString('ar', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        messageDiv.className = 'chat chat-end animate__animated animate__fadeInUp';
        messageDiv.innerHTML = `
            <div class="chat-image avatar">
                <div class="w-10 rounded-full bg-secondary text-secondary-content flex items-center justify-center">
                    <span class="material-icons">mic</span>
                </div>
            </div>
            <div class="chat-header">
                أنت
                <time class="text-xs opacity-50">${timestamp}</time>
            </div>
            <div class="chat-bubble chat-bubble-secondary">
                <audio controls class="w-full">
                    <source src="${audioUrl}" type="audio/webm">
                    <source src="${audioUrl}" type="audio/mpeg">
                    متصفحك لا يدعم تشغيل الصوت.
                </audio>
            </div>
        `;

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    /**
     * Format Message (convert newlines to <br>)
     */
    formatMessage(text) {
        return text
            .replace(/\n/g, '<br>')
            .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="link">$1</a>');
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show Loading Animation
     */
    showLoading() {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loadingMessage';
        loadingDiv.className = 'chat chat-start animate__animated animate__fadeInUp';
        loadingDiv.innerHTML = `
            <div class="chat-image avatar">
                <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                    <span class="material-icons">smart_toy</span>
                </div>
            </div>
            <div class="chat-header">
                مساعد HR
                <time class="text-xs opacity-50">يكتب...</time>
            </div>
            <div class="chat-bubble chat-bubble-primary">
                <div id="lottieLoaderContainer" style="width: 50px; height: 50px;"></div>
            </div>
        `;

        this.messagesContainer.appendChild(loadingDiv);
        this.scrollToBottom();

        // Start Lottie animation
        setTimeout(() => {
            const container = loadingDiv.querySelector('#lottieLoaderContainer');
            if (container && this.lottieAnimation) {
                this.lottieAnimation.play();
            }
        }, 100);
    }

    /**
     * Hide Loading Animation
     */
    hideLoading() {
        const loadingDiv = document.getElementById('loadingMessage');
        if (loadingDiv) {
            if (this.lottieAnimation) {
                this.lottieAnimation.stop();
            }
            loadingDiv.remove();
        }
    }

    /**
     * Scroll to Bottom
     */
    scrollToBottom() {
        setTimeout(() => {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }, 100);
    }

    /**
     * Toggle Voice Recording
     */
    async toggleVoiceRecording() {
        if (!this.isRecording) {
            await this.startRecording();
        } else {
            this.stopRecording();
        }
    }

    /**
     * Start Voice Recording
     */
    async startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                this.addAudioMessage(audioBlob);
                
                // TODO: Send audio to backend for processing
                // this.sendAudioToBackend(audioBlob);
                
                stream.getTracks().forEach(track => track.stop());
            };

            this.mediaRecorder.start();
            this.isRecording = true;
            this.recordingIndicator.classList.remove('hidden');
            this.voiceRecordBtn.classList.add('btn-error');
            this.voiceRecordBtn.querySelector('.material-icons').textContent = 'stop';
        } catch (error) {
            console.error('Error starting recording:', error);
            alert('لا يمكن الوصول إلى الميكروفون. يرجى التحقق من الصلاحيات.');
        }
    }

    /**
     * Stop Voice Recording
     */
    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            this.recordingIndicator.classList.add('hidden');
            this.voiceRecordBtn.classList.remove('btn-error');
            this.voiceRecordBtn.querySelector('.material-icons').textContent = 'mic';
        }
    }

    /**
     * Regenerate Response
     */
    async regenerateResponse(originalMessage) {
        // Show loading
        this.showLoading();

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: originalMessage,
                    regenerate: true
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Hide loading
            this.hideLoading();

            if (data.success) {
                this.addMessage(data.response, 'bot');
            } else {
                this.addMessage('عذراً، حدث خطأ: ' + (data.error || 'خطأ غير معروف'), 'bot', true);
            }
        } catch (error) {
            console.error('Regenerate Error:', error);
            this.hideLoading();
            this.addMessage('عذراً، حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.', 'bot', true);
        }
    }

    /**
     * Send Audio to Backend (TODO: Implement)
     */
    async sendAudioToBackend(audioBlob) {
        const formData = new FormData();
        formData.append('audio', audioBlob, 'recording.webm');
        formData.append('action', 'process_audio');

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();
            if (data.success && data.text) {
                // Add transcribed text as user message
                this.addMessage(data.text, 'user');
                // Send to bot
                this.messageInput.value = data.text;
                this.sendMessage();
            }
        } catch (error) {
            console.error('Audio processing error:', error);
        }
    }
}

// Initialize Chat Interface when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Set API URL from window if available
    if (typeof window.BOT_API_URL !== 'undefined') {
        window.chatInterface = new ChatInterface();
    } else {
        // Fallback: try to detect the API URL
        const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/');
        window.BOT_API_URL = baseUrl + '/api/hrbot.php';
        window.chatInterface = new ChatInterface();
    }
});

