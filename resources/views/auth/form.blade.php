<x-layouts.app :title="__($mode==='login'?'Sign in':($mode==='register'?'Create an account':'Reset password'))"><section class="auth-panel"><h1>{{ __($mode==='login'?'Welcome back':($mode==='register'?'A wardrobe of your own':'Reset your password')) }}</h1><form method="post" action="{{ $mode==='forgot'?route('password.email'):($mode==='reset'?route('password.update'):url('/'.$mode)) }}">@csrf
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
@if($mode==='register')<label>{{ __('Full name') }}<input name="name" value="{{ old('name') }}" required autocomplete="name"></label>@endif
<label>{{ __('Email') }}<input type="email" name="email" value="{{ old('email',$email??'') }}" required autocomplete="email"></label>
@if($mode!=='forgot')<label>{{ __('Password') }}<input type="password" name="password" required autocomplete="{{ $mode==='login'?'current-password':'new-password' }}"></label>@endif
@if(in_array($mode,['register','reset']))<p class="muted">{{ __('Use at least 12 characters with letters and numbers.') }}</p><label>{{ __('Confirm password') }}<input type="password" name="password_confirmation" required autocomplete="new-password"></label>@endif
@if($mode==='login')<label class="checkbox"><input type="checkbox" name="remember" value="1">{{ __('Remember me') }}</label>@endif
<button class="button full">{{ __($mode==='login'?'Sign in':($mode==='register'?'Create account':($mode==='forgot'?'Send reset link':'Reset password'))) }}</button></form>
@if($mode==='login')<a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a><p>{{ __('New here?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a></p>@else<a href="{{ route('login') }}">{{ __('Back to sign in') }}</a>@endif</section></x-layouts.app>
