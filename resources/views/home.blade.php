@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="font-mono text-[3.2rem] font-medium tracking-[-0.02em] text-primary">TempChat</h1>
            <p class="text-secondary font-sans text-[0.92rem] mt-3">Temporary anonymous chat rooms</p>
        </div>

        @if (session('error'))
            <div class="mb-4 p-3 rounded-[6px] bg-red-900/30 text-red-300 font-sans text-[0.85rem]">{{ session('error') }}</div>
        @endif

        @if (session('info'))
            <div class="mb-4 p-3 rounded-[6px] bg-tertiary/20 text-tertiary font-sans text-[0.85rem]">{{ session('info') }}</div>
        @endif

        <div class="bg-surface rounded-[10px] p-6">
            <form id="createRoomForm" action="{{ route('room.store') }}" method="POST">
                @csrf
                <label for="name" class="font-mono text-[0.72rem] text-secondary uppercase tracking-wider">Your Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="30"
                    required
                    placeholder="Enter your name..."
                    class="w-full mt-2 px-4 py-3 rounded-[6px] bg-neutral border border-secondary/30 text-primary font-sans text-[0.92rem] placeholder:text-secondary/50 focus:outline-none focus:border-tertiary transition-colors"
                >
                <button
                    type="submit"
                    class="w-full mt-5 px-5 py-3 rounded-[6px] bg-tertiary text-on-primary font-mono text-[0.85rem] font-medium hover:brightness-110 transition-all cursor-pointer"
                >
                    Create Room
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-secondary/20">
                <label for="joinCode" class="font-mono text-[0.72rem] text-secondary uppercase tracking-wider">Or Join a Room</label>
                <div class="flex gap-2 mt-2">
                    <input
                        type="text"
                        id="joinCode"
                        maxlength="8"
                        placeholder="Enter room code..."
                        class="flex-1 px-4 py-3 rounded-[6px] bg-neutral border border-secondary/30 text-primary font-sans text-[0.92rem] placeholder:text-secondary/50 focus:outline-none focus:border-tertiary transition-colors uppercase"
                    >
                    <button
                        onclick="joinRoom()"
                        class="px-5 py-3 rounded-[6px] border border-tertiary text-tertiary font-mono text-[0.85rem] font-medium hover:bg-tertiary/10 transition-all cursor-pointer"
                    >
                        Join
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function joinRoom() {
        const code = document.getElementById('joinCode').value.trim().toUpperCase();
        if (code.length < 4) {
            alert('Please enter a valid room code.');
            return;
        }
        window.location.href = '/room/' + code;
    }

    document.getElementById('joinCode').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') joinRoom();
    });
</script>
@endpush
