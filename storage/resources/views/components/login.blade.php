@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="max-w-md mx-auto {{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['title']))
        <h2 class="text-2xl font-bold text-center mb-6">{{ $data['title'] }}</h2>
    @endif
    <form action="{{ $data['action'] ?? '/login' }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">{{ $data['email_label'] ?? __('layup::frontend.login.email') }}</label>
            <input type="email" name="email" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-blue-500 dark:focus:border-blue-600 outline-none" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">{{ $data['password_label'] ?? __('layup::frontend.login.password') }}</label>
            <input type="password" name="password" required class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-blue-500 dark:focus:border-blue-600 outline-none" />
        </div>
        @if(!empty($data['remember_me']))
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" class="rounded" /> {{ __('layup::frontend.login.remember_me') }}
            </label>
        @endif
        <button type="submit" class="w-full bg-blue-600 dark:bg-blue-700 text-white font-medium py-2.5 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
            {{ $data['submit_text'] ?? __('layup::frontend.login.sign_in') }}
        </button>
        @if(!empty($data['forgot_url']) || !empty($data['register_url']))
            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                @if(!empty($data['forgot_url']))<a href="{{ $data['forgot_url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ __('layup::frontend.login.forgot_password') }}</a>@endif
                @if(!empty($data['register_url']))<a href="{{ $data['register_url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ __('layup::frontend.login.create_account') }}</a>@endif
            </div>
        @endif
    </form>
</div>
