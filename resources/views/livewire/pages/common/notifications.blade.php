<div class="am-allnotifications hz-notif" wire:ignore.self>
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v={{ @filemtime(public_path('css/notifications.css')) }}">
    <div class="am-allnotifications_content hz-notif__box">
        <div class="hz-notif__head">
            <h2>{{ __('general.notifications') }}</h2>
            @if(auth()->user()->unreadNotifications->isNotEmpty())
                <button type="button" class="hz-notif__all" wire:click="markAllAsRead">{{ __('general.mark_all_as_read') }}</button>
            @endif
        </div>
        @if($notifications->isNotEmpty())
            <ul class="hz-notif__list">
                @foreach($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $unread = empty($notification->read_at);
                        $linkText = trim((string) ($data['link_text'] ?? ''));
                        // Older notifications stored a translation key instead of text: translate it, never show the raw key.
                        if ($linkText !== '' && preg_match('/^[\w-]+(::[\w-]+)?\.[\w.]+$/', $linkText)) {
                            $translated = __($linkText);
                            $linkText = $translated === $linkText ? __('Ver detalles') : $translated;
                        }
                        $target = $data['link_target'] ?? null;
                        $hasLink = !empty($data['has_link']) && !empty($target) && $linkText !== '';
                        $body = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) ($data['content'] ?? '')))));
                    @endphp
                    <li class="hz-notif__item {{ $unread ? 'is-unread' : '' }}" wire:key="notif-{{ $notification->id }}">
                        <span class="hz-notif__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        </span>
                        <div class="hz-notif__body">
                            <div class="hz-notif__row">
                                <strong>{{ trim((string) ($data['subject'] ?? '')) ?: __('general.notifications') }}</strong>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ parseToUserTz($notification->created_at)->shortAbsoluteDiffForHumans() }}</time>
                            </div>
                            @if($body !== '')
                                <p>{{ $body }}</p>
                            @endif
                            @if($hasLink)
                                <a href="{{ $target }}" class="hz-notif__link" wire:click.prevent="markAsRead({{ \Illuminate\Support\Js::from($notification->id) }}, {{ \Illuminate\Support\Js::from($target) }})">{{ $linkText }} <span aria-hidden="true">→</span></a>
                            @endif
                        </div>
                        @if($unread)
                            <button type="button" class="hz-notif__read" wire:click="markAsRead({{ \Illuminate\Support\Js::from($notification->id) }})" title="{{ __('general.mark_as_read') }}" aria-label="{{ __('general.mark_as_read') }}"></button>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if(auth()->user()->notifications()->count() > $perPage)
                <div class="hz-notif__more">
                    <button type="button" class="hz-notif__morebtn" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">{{ __('general.load_more') }}</button>
                </div>
            @endif
        @else
            <div class="hz-notif__empty">
                <span class="hz-notif__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <h4>{{ __('general.all_caught_up') }}</h4>
                <p>{{ __('general.no_notification_desc') }}</p>
            </div>
        @endif
    </div>
</div>
