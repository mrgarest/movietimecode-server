@extends('layouts.page')


@section('body.main')
    <main class="space-y-8 px-4 pb-20 flex items-center justify-center h-full">
        <section class="mx-auto w-full max-w-lg text-center flex flex-col items-center gap-6">
            <img class="size-26 rounded-full select-none pointer-events-none" src="{{ asset('/favicon.png') }}">
            <h1 class="text-3xl sm:text-4xl text-foreground font-bold">Movie Timecode</h1>
            <h2 class="text-sm text-muted">Браузерне розширення для автоматичного приховування небажаних сцен під час
                трансляції
                фільмів на Twitch.</h2>
            <div class="space-y-4">
                <a class="h-10 sm:h-11 px-4 rounded-lg bg-primary text-primary-foreground hover:opacity-80 text-base sm:text-lg uppercase font-semibold flex items-center justify-center gap-2.5 select-none duration-300"
                    href="{{ env('EXTENSION_URL') }}" target="_blank">
                    <i class="fi fi-br-download pt-1"></i>
                    <span>Завантажити</span>
                </a>
                <a href="https://github.com/mrgarest/movietimecode-extension" target="_blank" rel="noopener noreferrer"
                class="text-sm text-muted hover:text-foreground hover:bg-primary/10 py-1.5 px-2.5 rounded-md duration-300  font-medium">GitHub</a>
            </div>
        </section>
    </main>
@endsection
