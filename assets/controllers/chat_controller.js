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

        // Connect to Mercure 
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
        messageDiv.className = `flex ${isMe ? 'justify-end' : 'justify-start'} animate-fade-in group/msg relative mb-4`;
        
        const avatarHtml = `
            <div class="flex-shrink-0 pt-0.5">
                <div class="w-10 h-10 rounded-full overflow-hidden border border-white/10 shadow-lg bg-slate-800 flex items-center justify-center ring-2 ring-white/5">
                    ${data.authorAvatar ? `<img src="/uploads/profiles/${data.authorAvatar}" class="w-full h-full object-cover">` : `<span class="text-[11px] text-slate-400 font-bold uppercase">${data.author.substring(0, 2)}</span>`}
                </div>
            </div>`;

        const bubbleClass = isMe 
            ? 'bg-indigo-600/90 text-white shadow-lg' 
            : 'bg-slate-800/80 text-slate-200 border border-white/[0.06] shadow-lg backdrop-blur-sm';

        let attachmentHtml = '';
        if (data.attachment) {
            const isImage = data.attachment.type && data.attachment.type.startsWith('image/');
            if (isImage) {
                const src = data.attachment.preview || `/uploads/chat/${data.attachment.path}`;
                attachmentHtml = `
                    <div class="mb-3 rounded-lg overflow-hidden border border-white/10 shadow-sm">
                        <img src="${src}" class="max-w-full max-h-64 object-cover" alt="Attachment">
                    </div>`;
            } else {
                const path = data.attachment.path ? `/uploads/chat/${data.attachment.path}` : '#';
                attachmentHtml = `
                    <a href="${path}" target="_blank" class="flex items-center gap-3 mb-3 p-3 bg-black/10 rounded-lg border border-white/5 hover:bg-black/20 transition-all group/file">
                        <div class="w-10 h-10 bg-indigo-500/20 rounded-lg flex items-center justify-center text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-white truncate">${data.attachment.name}</p>
                            <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">${data.attachment.type ? data.attachment.type.split('/')[1] : 'FILE'}</p>
                        </div>
                    </a>`;
            }
        }

        const nameLabel = isMe ? 'Vous' : data.author;
        const checkHtml = isMe ? `
            <div class="flex justify-end mt-1 -mb-0.5">
                <div class="flex items-center -space-x-1 opacity-50">
                    <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
            </div>` : '';

        const bubbleRounding = isMe ? 'rounded-2xl rounded-tr-md' : 'rounded-2xl rounded-tl-md';

        messageDiv.innerHTML = `
            <div class="flex ${isMe ? 'flex-row-reverse' : 'flex-row'} items-start gap-3 max-w-[85%] md:max-w-[70%] lg:max-w-[65%]">
                ${avatarHtml}
                <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} min-w-0">
                    <div class="flex items-baseline gap-2 mb-1 ${isMe ? 'flex-row-reverse' : ''}">
                        <span class="text-[13px] font-bold text-white leading-none">${nameLabel}</span>
                        <span class="text-[11px] text-slate-500 font-medium leading-none">${data.createdAt}</span>
                    </div>
                    <div class="relative ${bubbleClass} px-4 py-2.5 ${bubbleRounding} max-w-xl transition-all duration-300 shadow-xl">
                        ${attachmentHtml}
                        <p class="text-[14px] leading-relaxed font-medium whitespace-pre-wrap break-words">${this.escapeHtml(data.content)}</p>
                        ${checkHtml}
                    </div>
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
