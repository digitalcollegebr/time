@extends($layout)

@push('styles')
<style>
    /*
       Botão "Sign in with Google" conforme as diretrizes de marca do Google.
       Cores e medidas são intencionalmente fixas, sem tokens do tema: o visual
       do botão é definido pelo Google, e adaptá-lo ao magenta da marca o
       descaracterizaria. É também o que faz o usuário reconhecê-lo de imediato.
    */
    .google-signin-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        width: 100%;
        height: 40px;
        padding: 0 12px;
        background-color: #ffffff;
        border: 1px solid #747775;
        border-radius: 4px;
        font-family: "Roboto", "Montserrat", -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: 0.25px;
        color: #1f1f1f;
        text-decoration: none;
    }

    /* O tema define cor e sublinhado para <a>; reafirmamos nos estados para o
       botão não mudar de cara ao passar o mouse. */
    .google-signin-button:hover,
    .google-signin-button:focus,
    .google-signin-button:active {
        background-color: #f2f2f2;
        border-color: #747775;
        color: #1f1f1f;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(60, 64, 67, 0.3);
    }

    .google-signin-button:focus-visible {
        outline: 2px solid #4285f4;
        outline-offset: 2px;
    }

    .google-signin-button__icon {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')

@dispatchEvent('beforePageHeaderOpen')
<div class="pageheader">
    @dispatchEvent('afterPageHeaderOpen')
    <div class="pagetitle">
        <h1>{!! __('headlines.login') !!}</h1>
    </div>
    @dispatchEvent('beforePageHeaderClose')
</div>
@dispatchEvent('afterPageHeaderClose')

<div class="regcontent">
    @dispatchEvent('afterRegcontentOpen')
    {!! $tpl->displayInlineNotification() !!}

    @if ($noLoginForm === false)
        <form id="login" action="{{ BASE_URL }}/auth/login" method="post">
            @csrf
            @dispatchEvent('afterFormOpen')
        <input type="hidden" name="redirectUrl" value="{{ $redirectUrl }}" />

        <div class="">
            <label for="username">Email</label>
            <input type="text" name="username" id="username" class="form-control" placeholder="{{ __($inputPlaceholder) }}" value=""/>
        </div>
        <div class="">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" autocomplete="off" class="form-control" placeholder="{{ __('input.placeholders.enter_password') }}" value=""/>
            <div class="forgotPwContainer">
                <a href="{{ BASE_URL }}/auth/resetPw" class="forgotPw">{!! __('links.forgot_password') !!}</a>
            </div>
        </div>
            @dispatchEvent('beforeSubmitButton')
        <div class="">
            <input type="submit" name="login" value="{{ __('buttons.login') }}" class="btn btn-primary"/>
        </div>
        <div>
        </div>
            @dispatchEvent('beforeFormClose')

    </form>
    @else
        {!! __('text.no_login_form') !!}<br /><br />
    @endif

    @if ($oidcEnabled)

        @dispatchEvent('beforeOidcButton')

        <div class="">
            <div style="margin-top:20px; border-bottom:1px solid #ccc; with:100%; height:10px; overflow:show; text-align:center; margin-bottom:40px;">
                <p style="text-align:center; display:inline-block; background:var(--secondary-background); padding:0px 5px;">{!! __('label.or_login_with') !!}</p>
            </div>
            {{--
                Botão padronizado do Google ("Sign in with Google").

                As cores e as medidas são fixas de propósito e NÃO usam os tokens do
                tema: as diretrizes de marca do Google definem o visual do botão, e
                pintá-lo de magenta como os demais o descaracterizaria. Reconhecer o
                botão de imediato também é o que se espera do ponto de vista de UX.

                O logo vai embutido como SVG em vez de vir de um CDN do Google — a
                tela de login não deve depender de recurso externo para renderizar.
            --}}
            <a href="{{ BASE_URL }}/oidc/login" class="google-signin-button">
                <span class="google-signin-button__icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                </span>
                <span class="google-signin-button__label">{!! __('buttons.oidclogin') !!}</span>
            </a>
        </div>
    @endif

    @dispatchEvent('beforeRegcontentClose')
</div>

@endsection
