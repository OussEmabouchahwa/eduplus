import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'input'];
    static values = {
        courseId: Number,
        hubUrl: String,
        currentUser: String
    };

    connect() {
        this.scrollToBottom();

        // Connect to Mercure SSE Hub if url is present
        if (this.hasHubUrlValue && this.hubUrlValue) {
            const url = new URL(this.hubUrlValue, window.location.origin);
            url.searchParams.append('topic', `https://edupulse.com/chat/${this.courseIdValue}`);
            
            this.eventSource = new EventSource(url);
            
            this.eventSource.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.appendMessage(data);
            };
            
            this.eventSource.onerror = (error) => {
                console.error("Mercure EventSource failed:", error);
            };
        }
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    async submit(event) {
        event.preventDefault();
        
        const content = this.inputTarget.value.trim();
        const fileInput = document.getElementById('attachment-input');
        const file = fileInput ? fileInput.files[0] : null;

        if (!content && !file) return;

        // Immediately show the message in the UI (Optimistic Update)
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        const optimisticData = {
            content: content,
            author: this.currentUserValue,
            createdAt: timeString,
            isOptimistic: true
        };

        if (file) {
            optimisticData.attachment = {
                name: file.name,
                type: file.type,
                preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
            };
        }

        this.appendMessage(optimisticData);

        // Reset input
        this.inputTarget.value = '';
        this.inputTarget.style.height = 'auto';
        if (fileInput) fileInput.value = '';
        const preview = document.getElementById('attachment-preview');
        if (preview) preview.classList.add('hidden');
        
        try {
            const formData = new FormData();
            formData.append('content', content);
            if (file) {
                formData.append('attachment', file);
            }

            const response = await fetch(`/chat/send/${this.courseIdValue}`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                console.error('Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    appendMessage(data) {
        const isMe = data.author === this.currentUserValue;
        
        if (isMe && !data.isOptimistic) {
            return;
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${isMe ? 'justify-end' : 'justify-start'} animate-fade-in group/msg`;
        
        let authorHtml = '';
        if (!isMe) {
            authorHtml = `
            <div class="w-10 h-10 rounded-2xl overflow-hidden mr-4 mt-auto border border-white/10 flex-shrink-0 shadow-lg bg-slate-800 flex items-center justify-center text-[10px] text-slate-400 font-bold uppercase">
                ${data.author.substring(0, 2)}
            </div>`;
        }

        const bubbleClass = isMe 
            ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-600/20 rounded-3xl rounded-br-lg' 
            : 'bg-white/10 backdrop-blur-md text-slate-200 border border-white/5 shadow-xl rounded-3xl rounded-bl-lg';

        let attachmentHtml = '';
        if (data.attachment) {
            if (data.attachment.type.startsWith('image/')) {
                const src = data.attachment.preview || `/uploads/chat/${data.attachment.path}`;
                attachmentHtml = `
                    <div class="mb-3 rounded-2xl overflow-hidden border border-white/10">
                        <img src="${src}" class="max-w-full max-h-64 object-cover" alt="Attachment">
                    </div>`;
            } else {
                const path = data.attachment.path ? `/uploads/chat/${data.attachment.path}` : '#';
                attachmentHtml = `
                    <a href="${path}" target="_blank" class="flex items-center gap-3 mb-3 p-3 bg-white/5 rounded-2xl border border-white/10 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-white truncate">${data.attachment.name}</p>
                            <p class="text-[10px] text-slate-500 uppercase">${data.attachment.type.split('/')[1]}</p>
                        </div>
                    </a>`;
            }
        }

        let nameHtml = !isMe ? `<span class="text-[11px] text-slate-500 font-bold ml-1 mb-1.5">${data.author}</span>` : '';
        const checkHtml = isMe ? `<svg class="w-3 h-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>` : '';

        messageDiv.innerHTML = `
            ${authorHtml}
            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[75%]">
                ${nameHtml}
                <div class="${bubbleClass} p-5 transition-all hover:scale-[1.01] duration-300">
                    ${attachmentHtml}
                    <p class="text-[15.5px] leading-relaxed font-medium">${this.escapeHtml(data.content)}</p>
                </div>
                <div class="flex items-center gap-2 mt-2 mx-2">
                    <span class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">${data.createdAt}</span>
                    ${checkHtml}
                </div>
            </div>
        `;
        
        this.containerTarget.appendChild(messageDiv);
        this.scrollToBottom();
    }

    scrollToBottom() {
        this.element.scrollTop = this.element.scrollHeight;
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
}
