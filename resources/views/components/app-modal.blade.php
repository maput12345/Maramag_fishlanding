@props([
    'title' => null,
    'subtitle' => null,
    'closeUrl' => url()->current(),
    'maxWidth' => 'md',
    'showClose' => true,
    'panelClass' => '',
    'bodyClass' => '',
])

@php
    $titleId = 'app-modal-title-' . substr(md5(($title ?? 'dialog') . '|' . $closeUrl . '|' . $maxWidth), 0, 8);
    $widthClass = match ($maxWidth) {
        'sm' => 'workspace-popup__panel--sm',
        'md' => '',
        'lg' => 'workspace-popup__panel--lg',
        'xl' => 'workspace-popup__panel--xl',
        '2xl' => 'workspace-popup__panel--2xl',
        '7xl' => 'workspace-popup__panel--7xl',
        default => '',
    };
@endphp

<div
    x-data="{
        open: true,
        modalId: @js($titleId),
        closeUrl: @js($closeUrl),
        init() {
            document.documentElement.classList.add('modal-scroll-lock');
            document.body.classList.add('modal-scroll-lock');
        },
        syncUrl() {
            if (!this.closeUrl) {
                return;
            }

            try {
                const targetUrl = new URL(this.closeUrl, window.location.origin);

                if (targetUrl.origin === window.location.origin) {
                    window.history.replaceState(
                        { ...(window.history.state || {}), modalClosed: true },
                        '',
                        `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`
                    );
                    return;
                }
            } catch (error) {
                console.warn('Unable to sync modal URL without navigation.', error);
            }

            window.location.assign(this.closeUrl);
        },
        close() {
            document.documentElement.classList.remove('modal-scroll-lock');
            document.body.classList.remove('modal-scroll-lock');
            this.open = false;

            if (this.closeUrl) {
                this.syncUrl();
            }

            window.dispatchEvent(new CustomEvent('app-modal-closed', {
                detail: {
                    id: this.modalId,
                    closeUrl: this.closeUrl
                }
            }));
        }
    }"
    class="contents"
>
    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            class="workspace-popup"
            data-app-modal-root
            data-close-url="{{ $closeUrl }}"
            data-modal-id="{{ $titleId }}"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $titleId }}"
            style="display: none;"
            @keydown.escape.window.prevent="close()"
        >
            <div class="workspace-popup__stage">
                <button type="button" class="workspace-popup__backdrop" aria-label="Close dialog" @click="close()"></button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="workspace-popup__panel {{ $widthClass }} {{ $panelClass }}"
                    @click.stop
                >
                    @if($title || isset($icon))
                        <div class="workspace-popup__header">
                            <div class="workspace-popup__heading">
                                @isset($icon)
                                    <div class="workspace-popup__icon">
                                        {{ $icon }}
                                    </div>
                                @endisset

                                <div class="min-w-0">
                                    @if($title)
                                        <h3 id="{{ $titleId }}" class="workspace-popup__title">{{ $title }}</h3>
                                    @endif

                                    @if($subtitle)
                                        <p class="workspace-popup__subtitle">{{ $subtitle }}</p>
                                    @endif
                                </div>
                            </div>

                            @if($showClose)
                                <button
                                    type="button"
                                    class="workspace-popup__close"
                                    aria-label="Close modal"
                                    data-app-modal-close
                                    @click="close()"
                                >
                                    <x-heroicon-o-x-mark class="h-5 w-5" />
                                </button>
                            @endif
                        </div>
                    @endif

                    <div class="workspace-popup__body {{ $bodyClass }}">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
