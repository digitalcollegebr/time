@if(app(\Leantime\Domain\Supportchat\Services\Supportchat::class)->isConfigured())
@php($supportBrand = session('companysettings.sitename') ?: 'Time')
<style>
    #gtiSupportFab {
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        width: 56px; height: 56px; border-radius: 50%; border: none; cursor: pointer;
        background: var(--accent1); color: #fff; font-size: 22px;
        box-shadow: var(--regular-shadow, 0 4px 14px rgba(0,0,0,.25));
        transition: transform .15s ease;
    }
    #gtiSupportFab:hover { transform: scale(1.07); }
    #gtiSupportPanel {
        position: fixed; bottom: 92px; right: 24px; z-index: 9999;
        width: 370px; max-width: calc(100vw - 32px); height: 520px; max-height: calc(100vh - 120px);
        display: none; flex-direction: column; overflow: hidden;
        background: var(--layered-background, #fff);
        border: 1px solid var(--main-border-color, #e2e4e9);
        border-radius: var(--box-radius, 12px);
        box-shadow: var(--large-shadow, 0 12px 40px rgba(0,0,0,.3));
        font-family: var(--primary-font-family, 'Inter', sans-serif);
    }
    #gtiSupportPanel.open { display: flex; }
    .gtiSupportHeader {
        background: var(--header-gradient, var(--accent1));
        color: var(--main-titles-color, #fff);
        padding: 14px 16px; display: flex; align-items: center; gap: 10px;
    }
    .gtiSupportHeader strong { font-size: var(--font-size-m, 15px); flex: 1; }
    .gtiSupportHeader small { display: block; opacity: .85; font-weight: normal; }
    .gtiSupportHeader button { background: none; border: none; color: inherit; cursor: pointer; font-size: 16px; }
    #gtiSupportMessages {
        flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px;
    }
    .gtiMsg {
        max-width: 85%; padding: 9px 12px; border-radius: 14px;
        font-size: var(--font-size-s, 13.5px); line-height: 1.45; white-space: pre-wrap; word-wrap: break-word;
    }
    .gtiMsg.user { align-self: flex-end; background: var(--accent1); color: #fff; border-bottom-right-radius: 4px; }
    .gtiMsg.bot { align-self: flex-start; background: var(--dropdown-link-hover-bg, #f0f1f6); color: var(--primary-font-color, #333); border-bottom-left-radius: 4px; }
    .gtiMsg.typing { opacity: .65; font-style: italic; }
    .gtiSupportInputRow {
        display: flex; gap: 8px; padding: 12px; border-top: 1px solid var(--main-border-color, #e2e4e9);
    }
    #gtiSupportInput {
        flex: 1; padding: 9px 12px; border: 1px solid var(--main-border-color, #ccc);
        border-radius: var(--element-radius, 8px); font-family: inherit; font-size: var(--font-size-s, 13.5px);
        background: var(--input-background, #fff); color: var(--primary-font-color, #333);
    }
    #gtiSupportSend {
        border: none; border-radius: var(--element-radius, 8px); cursor: pointer;
        background: var(--accent1); color: #fff; padding: 0 16px; font-size: 15px;
    }
    #gtiSupportSend:disabled { opacity: .5; cursor: wait; }
</style>

<button id="gtiSupportFab" type="button" aria-label="Abrir chat de suporte" title="Fale com o Time Bot">
    <i class="fa-solid fa-robot"></i>
</button>

<div id="gtiSupportPanel" role="dialog" aria-label="Chat de suporte">
    <div class="gtiSupportHeader">
        <i class="fa-solid fa-robot"></i>
        <strong>Time Bot<small>Assistente de Projetos</small></strong>
        <button type="button" id="gtiSupportClose" aria-label="Fechar"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="gtiSupportMessages"></div>
    <div class="gtiSupportInputRow">
        <input id="gtiSupportInput" type="text" placeholder="Pergunte sobre o {{ $supportBrand }} ou IA Generativa..." maxlength="2000" />
        <button id="gtiSupportSend" type="button" aria-label="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<script>
(function () {
    const fab = document.getElementById('gtiSupportFab');
    const panel = document.getElementById('gtiSupportPanel');
    const messagesEl = document.getElementById('gtiSupportMessages');
    const input = document.getElementById('gtiSupportInput');
    const sendBtn = document.getElementById('gtiSupportSend');
    const WELCOME = 'E aí! 👋 Sou o Time Bot, seu parceiro aqui no {{ $supportBrand }}. Manda tua dúvida sobre o sistema ou sobre os projetos de IA Generativa!';

    let history = [];
    try { history = JSON.parse(sessionStorage.getItem('gtiSupportHistory') || '[]'); } catch (e) { history = []; }

    function persist() { sessionStorage.setItem('gtiSupportHistory', JSON.stringify(history.slice(-40))); }

    function addBubble(role, text) {
        const div = document.createElement('div');
        div.className = 'gtiMsg ' + role;
        div.textContent = text;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return div;
    }

    function renderHistory() {
        messagesEl.innerHTML = '';
        if (history.length === 0) { addBubble('bot', WELCOME); return; }
        history.forEach(m => addBubble(m.role, m.text));
    }

    // Captura o contexto da tela atual (rota + título + texto visível, truncado).
    // O widget fica fora do container principal, então não polui a captura.
    function screenContext() {
        const main = document.querySelector('.maincontent') || document.querySelector('.mainwrapper') || document.body;
        const text = (main.innerText || '').replace(/\n{3,}/g, '\n\n').trim().slice(0, 3500);
        return 'Tela: ' + document.title + ' (' + location.pathname + ')\n' + text;
    }

    async function send() {
        const message = input.value.trim();
        if (!message || sendBtn.disabled) return;
        input.value = '';
        addBubble('user', message);
        history.push({ role: 'user', text: message }); persist();

        sendBtn.disabled = true; input.disabled = true;
        const typing = addBubble('bot', 'Time Bot está digitando...');
        typing.classList.add('typing');

        try {
            const res = await fetch('{{ rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?: '', '/') }}/supportchat/message', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                },
                body: JSON.stringify({
                    message: message,
                    threadId: sessionStorage.getItem('gtiSupportThread') || null,
                    screenContext: screenContext()
                })
            });
            const data = await res.json();
            typing.remove();
            if (!res.ok) throw new Error(data.error || 'Erro inesperado');
            sessionStorage.setItem('gtiSupportThread', data.threadId);
            addBubble('bot', data.reply);
            history.push({ role: 'bot', text: data.reply }); persist();
        } catch (e) {
            typing.remove();
            addBubble('bot', '😅 ' + (e.message || 'Não consegui responder agora. Tenta de novo?'));
        } finally {
            sendBtn.disabled = false; input.disabled = false; input.focus();
        }
    }

    fab.addEventListener('click', function () {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) { renderHistory(); input.focus(); }
    });
    document.getElementById('gtiSupportClose').addEventListener('click', () => panel.classList.remove('open'));
    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') send(); });
})();
</script>
@endif
