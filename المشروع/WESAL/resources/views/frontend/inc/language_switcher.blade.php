@php
    $activeWebsiteLocale = app()->getLocale() === 'ar' ? 'ar' : 'en';
@endphp
<form class="wesal-language-switcher" method="POST" action="{{ route('language.change') }}" role="group" aria-label="{{ $activeWebsiteLocale === 'ar' ? 'تغيير اللغة' : 'Change language' }}">
    @csrf
    <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
    <button type="submit" name="locale" value="ar" class="{{ $activeWebsiteLocale === 'ar' ? 'is-active' : '' }}" aria-pressed="{{ $activeWebsiteLocale === 'ar' ? 'true' : 'false' }}" lang="ar" dir="rtl">العربية</button>
    <button type="submit" name="locale" value="en" class="{{ $activeWebsiteLocale === 'en' ? 'is-active' : '' }}" aria-pressed="{{ $activeWebsiteLocale === 'en' ? 'true' : 'false' }}" lang="en" dir="ltr">English</button>
</form>
