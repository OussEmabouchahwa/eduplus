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
        if (!content) return;

        // Immediately show the message in the UI (Optimistic Update)
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        this.appendMessage({
            content: content,
            author: this.currentUserValue,
            createdAt: timeString,
            isOptimistic: true // Custom flag to identify local messages
        });

        this.inputTarget.value = '';
        this.inputTarget.style.height = 'auto'; // Reset textarea height
        
        try {
            const response = await fetch(`/chat/send/${this.courseIdValue}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ content: content })
            });

            if (!response.ok) {
                console.error('Failed to send message');
                // Could remove the optimistic message here or show error state
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
    }

    appendMessage(data) {
        const isMe = data.author === this.currentUserValue;
        
        // Ignore messages coming from Mercure if they are from me, 
        // because I already added them optimistically!
        if (isMe && !data.isOptimistic) {
            return;
        }
        
        // Basic template for a message
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

        let nameHtml = !isMe ? `<span class="text-[11px] text-slate-500 font-bold ml-1 mb-1.5">${data.author}</span>` : '';

        const checkHtml = isMe ? `<svg class="w-3 h-3 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"></path></svg>` : '';

        messageDiv.innerHTML = `
            ${authorHtml}
            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[75%]">
                ${nameHtml}
                <div class="${bubbleClass} p-5 transition-all hover:scale-[1.01] duration-300">
                    <p class="text-[15.5px] leading-relaxed font-medium">${this.escapeHtml(data.content)}</p>
                </div>
                <div class="flex items-center gap-2 mt-2 mx-2">
                    <span class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">${data.createdAt}</span>
                    ${checkHtml}
                </div>
            </div>
        `;
        
        // Remove empty state message if it exists
        const emptyState = this.containerTarget.querySelector('.italic');
        if (emptyState && emptyState.closest('.py-20')) {
            emptyState.closest('.py-20').remove();
        }

        this.containerTarget.appendChild(messageDiv);
        this.scrollToBottom();
    }

    scrollToBottom() {
        // Scroll the main chat zone (which is 'this.element')
        this.element.scrollTop = this.element.scrollHeight;
    }

    escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
}
