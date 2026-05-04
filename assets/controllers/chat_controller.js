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
            <div class="w-8 h-8 rounded-full overflow-hidden mr-2 mt-auto border border-slate-600/50 flex-shrink-0 bg-slate-700 flex items-center justify-center text-[10px] text-slate-300 font-bold uppercase">
                ${data.author.substring(0, 2)}
            </div>`;
        }

        const bubbleClass = isMe 
            ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm' 
            : 'bg-slate-800 text-slate-200 border border-slate-700/50 rounded-2xl rounded-bl-sm';

        let nameHtml = !isMe ? `<span class="text-[10px] text-slate-400 font-semibold ml-1 mb-1">${data.author}</span>` : '';

        messageDiv.innerHTML = `
            ${authorHtml}
            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[70%]">
                ${nameHtml}
                <div class="${bubbleClass} p-3.5 shadow-md relative group-hover/msg:shadow-lg transition-all">
                    <p class="text-sm leading-relaxed">${this.escapeHtml(data.content)}</p>
                </div>
                <span class="text-[10px] text-slate-500 mt-1 mx-1">${data.createdAt}</span>
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
