@extends('layouts.app')

@section('title', 'Room ' . $room->code)

@section('content')
<div class="min-h-screen flex flex-col">
    {{-- Header --}}
    <header class="bg-surface border-b border-secondary/20 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="text-secondary hover:text-primary transition-colors font-mono text-[0.85rem]">&larr;</a>
            <div>
                <h1 class="font-mono text-[0.85rem] text-primary font-medium">{{ $room->code }}</h1>
                <div class="flex items-center gap-3 mt-0.5">
                    <span class="font-mono text-[0.72rem] text-secondary" id="onlineCount">0 online</span>
                    <span class="font-mono text-[0.72rem] text-secondary" id="countdown">--:--:--</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="toggleSound()" id="soundToggle" class="p-2 rounded-[6px] text-secondary hover:text-primary hover:bg-neutral/50 transition-all cursor-pointer" title="Toggle sound">
                <svg id="soundOn" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                <svg id="soundOff" class="hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
            </button>
            <button onclick="copyLink()" class="p-2 rounded-[6px] text-secondary hover:text-primary hover:bg-neutral/50 transition-all cursor-pointer" title="Copy link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button onclick="shareRoom()" class="p-2 rounded-[6px] text-secondary hover:text-primary hover:bg-neutral/50 transition-all cursor-pointer" title="Share">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </button>
            <form action="{{ route('room.leave', $room->code) }}" method="POST" class="inline" onsubmit="return confirm('Leave this room?')">
                @csrf
                <button type="submit" class="p-2 rounded-[6px] text-secondary hover:text-red-400 hover:bg-red-900/20 transition-all cursor-pointer" title="Leave room">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </header>

    {{-- Join prompt if no session --}}
    @if (!$userId)
    <div class="flex-1 flex items-center justify-center px-4">
        <div class="bg-surface rounded-[10px] p-6 w-full max-w-sm">
            <h2 class="font-mono text-[1.2rem] text-primary font-medium text-center">Join Room</h2>
            <p class="text-secondary text-center font-sans text-[0.85rem] mt-2">Enter your name to join <span class="font-mono text-tertiary">{{ $room->code }}</span></p>
            <form id="joinForm" class="mt-5">
                @csrf
                <input
                    type="text"
                    id="joinName"
                    maxlength="30"
                    required
                    placeholder="Your name..."
                    class="w-full px-4 py-3 rounded-[6px] bg-neutral border border-secondary/30 text-primary font-sans text-[0.92rem] placeholder:text-secondary/50 focus:outline-none focus:border-tertiary transition-colors"
                >
                <button
                    type="submit"
                    id="joinBtn"
                    class="w-full mt-4 px-5 py-3 rounded-[6px] bg-tertiary text-on-primary font-mono text-[0.85rem] font-medium hover:brightness-110 transition-all cursor-pointer"
                >
                    Join Room
                </button>
            </form>
        </div>
    </div>
    @else
    {{-- Chat area --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3" id="messagesContainer">
        <div id="emptyState" class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-16 h-16 rounded-full bg-surface flex items-center justify-center mb-4">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-secondary"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <p class="text-secondary font-sans text-[0.92rem]">No messages yet</p>
            <p class="text-secondary/60 font-sans text-[0.8rem] mt-1">Send a message to start the conversation</p>
        </div>
    </div>

    {{-- Typing indicator --}}
    <div id="typingIndicator" class="px-4 py-1 text-secondary font-sans text-[0.8rem] hidden">
        <span id="typingText"></span>
        <span class="inline-flex gap-0.5 ml-1">
            <span class="w-1.5 h-1.5 bg-secondary rounded-full animate-bounce" style="animation-delay:0ms"></span>
            <span class="w-1.5 h-1.5 bg-secondary rounded-full animate-bounce" style="animation-delay:150ms"></span>
            <span class="w-1.5 h-1.5 bg-secondary rounded-full animate-bounce" style="animation-delay:300ms"></span>
        </span>
    </div>

    {{-- Input --}}
    <div class="bg-surface border-t border-secondary/20 px-4 py-3">
        <form id="messageForm" class="flex gap-2">
            @csrf
            <input
                type="text"
                id="messageInput"
                maxlength="500"
                autocomplete="off"
                placeholder="Type a message..."
                class="flex-1 px-4 py-3 rounded-[6px] bg-neutral border border-secondary/30 text-primary font-sans text-[0.92rem] placeholder:text-secondary/50 focus:outline-none focus:border-tertiary transition-colors"
            >
            <button
                type="submit"
                id="sendBtn"
                class="px-5 py-3 rounded-[6px] bg-tertiary text-on-primary font-mono text-[0.85rem] font-medium hover:brightness-110 transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Send
            </button>
        </form>
    </div>
    @endif
</div>

{{-- Toast --}}
<div id="toast" class="fixed top-4 right-4 z-50 px-4 py-3 rounded-[6px] font-sans text-[0.85rem] shadow-lg transition-all duration-300 translate-x-[120%] opacity-0"></div>
@endsection

@push('scripts')
<script>
    const roomCode = '{{ $room->code }}';
    const userId = {{ $userId ?? 'null' }};
    const roomExpiredAt = '{{ $expiredAt }}';
    let lastMessageId = 0;
    let isSoundOn = localStorage.getItem('soundEnabled') !== 'false';
    let typingTimer = null;

    const messagesContainer = document.getElementById('messagesContainer');
    const emptyState = document.getElementById('emptyState');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const typingIndicator = document.getElementById('typingIndicator');
    const typingText = document.getElementById('typingText');
    const toast = document.getElementById('toast');
    const onlineCount = document.getElementById('onlineCount');
    const countdownEl = document.getElementById('countdown');
    const joinForm = document.getElementById('joinForm');

    function playNotificationSound() {
        if (!isSoundOn) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 660;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }

    function toggleSound() {
        isSoundOn = !isSoundOn;
        localStorage.setItem('soundEnabled', isSoundOn);
        document.getElementById('soundOn').classList.toggle('hidden', !isSoundOn);
        document.getElementById('soundOff').classList.toggle('hidden', isSoundOn);
    }

    function showToast(message, type = 'info') {
        toast.textContent = message;
        toast.className = 'fixed top-4 right-4 z-50 px-4 py-3 rounded-[6px] font-sans text-[0.85rem] shadow-lg transition-all duration-300';
        if (type === 'success') toast.classList.add('bg-green-900/80', 'text-green-200');
        else if (type === 'error') toast.classList.add('bg-red-900/80', 'text-red-200');
        else toast.classList.add('bg-tertiary/20', 'text-tertiary');
        toast.classList.remove('translate-x-[120%]', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-x-[120%]', 'opacity-0');
        }, 3000);
    }

    function copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link copied to clipboard!', 'success');
        }).catch(() => {
            showToast('Failed to copy link', 'error');
        });
    }

    function shareRoom() {
        const url = window.location.href;
        const text = 'Join my TempChat room: ' + roomCode;
        if (navigator.share) {
            navigator.share({ title: 'TempChat', text: text, url: url });
        } else {
            const whatsapp = 'https://wa.me/?text=' + encodeURIComponent(text + '\n' + url);
            const telegram = 'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(text);
            const choice = confirm('Share via:\nOK = WhatsApp\nCancel = Telegram');
            window.open(choice ? whatsapp : telegram, '_blank');
        }
    }

    function api() {
        if (typeof window.axios !== 'undefined') {
            return window.axios;
        }
        return axios;
    }

    if (joinForm) {
        joinForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('joinName').value.trim();
            if (!name) return;
            const btn = document.getElementById('joinBtn');
            btn.disabled = true;
            btn.textContent = 'Joining...';

            api().post('{{ route("room.join", $room->code) }}', { name: name })
                .then(r => {
                    if (r.data.success) {
                        location.reload();
                    } else {
                        showToast(r.data.error || 'Failed to join', 'error');
                        btn.disabled = false;
                        btn.textContent = 'Join Room';
                    }
                })
                .catch(e => {
                    console.error('Join error:', e.response || e);
                    showToast('Connection error', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Join Room';
                });
        });
    }

    function updateCountdown() {
        const now = new Date();
        const expired = new Date(roomExpiredAt);
        const diff = expired - now;
        if (diff <= 0) {
            countdownEl.textContent = 'Expired';
            countdownEl.classList.add('text-red-400');
            return;
        }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        countdownEl.textContent =
            String(h).padStart(2, '0') + ':' +
            String(m).padStart(2, '0') + ':' +
            String(s).padStart(2, '0');
        if (diff < 3600000) {
            countdownEl.classList.add('text-red-400');
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    if (messageInput) {
        const messageForm = document.getElementById('messageForm');

        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const msg = messageInput.value.trim();
            if (!msg) return;
            sendBtn.disabled = true;

            api().post('{{ route("room.messages.store", $room->code) }}', { message: msg })
                .then(r => {
                    if (r.data.success) {
                        messageInput.value = '';
                        appendMessage(r.data.message, true);
                        smartScroll();
                    } else {
                        showToast(r.data.error || 'Failed to send', 'error');
                    }
                })
                .catch(e => {
                    console.error('Send error:', e.response || e);
                    const msg = e.response?.status === 429
                        ? 'Too many requests, please slow down'
                        : 'Connection error';
                    showToast(msg, 'error');
                })
                .finally(() => { sendBtn.disabled = false; messageInput.focus(); });
        });

        messageInput.addEventListener('input', function() {
            if (typingTimer) clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                if (messageInput.value.trim()) {
                    api().post('{{ route("room.typing", $room->code) }}').catch(e => console.error('Typing error:', e.response || e));
                }
            }, 300);
        });
    }

    function fetchMessages() {
        if (!userId) return;
        api().get('{{ route("room.messages", $room->code) }}')
            .then(r => {
                const data = r.data;
                if (data.messages) {
                    const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 50;
                    const hasNewFromOthers = data.messages.some(m => m.id > lastMessageId && m.user_id !== userId);

                    data.messages.forEach(m => {
                        if (m.id > lastMessageId) {
                            appendMessage(m, m.user_id === userId);
                        }
                    });

                    if (data.messages.length > 0) {
                        lastMessageId = data.messages[data.messages.length - 1].id;
                    }

                    if (hasNewFromOthers && isSoundOn) {
                        playNotificationSound();
                    }

                    if (wasAtBottom) smartScroll();
                }
                if (data.online_count !== undefined) {
                    onlineCount.textContent = data.online_count + ' online';
                }
            })
            .catch(e => console.error('Fetch messages error:', e.response || e));
    }

    function fetchTypingStatus() {
        if (!userId) return;
        api().get('{{ route("room.typing.status", $room->code) }}')
            .then(r => {
                const data = r.data;
                if (data.typing && data.typing.length > 0) {
                    typingText.textContent = data.typing.join(', ') + ' typing';
                    typingIndicator.classList.remove('hidden');
                } else {
                    typingIndicator.classList.add('hidden');
                }
            })
            .catch(e => console.error('Fetch typing status error:', e.response || e));
    }

    function appendMessage(msg, isSelf) {
        if (emptyState) emptyState.remove();

        const existing = document.getElementById('msg-' + msg.id);
        if (existing) return;

        const div = document.createElement('div');
        div.id = 'msg-' + msg.id;
        div.className = 'flex ' + (isSelf ? 'justify-end' : 'justify-start');

        const maxWidth = msg.message.length > 50 ? 'max-w-[85%]' : 'max-w-[70%]';

        div.innerHTML =
            '<div class="' + maxWidth + ' ' +
            (isSelf
                ? 'bg-tertiary text-on-primary rounded-tl-[10px] rounded-tr-[10px] rounded-bl-[10px]'
                : 'bg-surface text-primary rounded-tl-[10px] rounded-tr-[10px] rounded-br-[10px]'
            ) + ' px-4 py-2.5">' +
            (!isSelf ? '<div class="font-mono text-[0.72rem] text-tertiary mb-1">' + msg.name + '</div>' : '') +
            '<div class="font-sans text-[0.92rem] leading-[1.55]">' + msg.message + '</div>' +
            '<div class="text-right font-mono text-[0.65rem] ' + (isSelf ? 'text-on-primary/60' : 'text-secondary') + ' mt-1">' + msg.time + '</div>' +
            '</div>';

        messagesContainer.appendChild(div);
    }

    setInterval(fetchMessages, 2000);
    setInterval(fetchTypingStatus, 2000);

    function smartScroll() {
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 50);
    }

    if (userId) {
        fetchMessages();
        fetchTypingStatus();
    }

    if (!isSoundOn) {
        document.getElementById('soundOn').classList.add('hidden');
        document.getElementById('soundOff').classList.remove('hidden');
    }
</script>
@endpush
